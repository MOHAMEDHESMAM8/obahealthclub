<?php
/**
 * Product Shortcodes for Membership Pricing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the membership label functions
require_once(plugin_dir_path(__FILE__) . 'membership-label.php');

/**
 * Get user's age category based on date of birth
 */
function get_user_age_category($birth_date) {
    if (empty($birth_date)) {
        return '';
    }
    
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
    
    if ($age < 25) {
        return 'Youth';
    } elseif ($age >= 25 && $age <= 35) {
        return 'Young Adult';
    } elseif ($age >= 36 && $age <= 50) {
        return 'Adult';
    } else {
        return 'Mature Adult';
    }
}

/**
 * Shortcode to display products with gender and age-specific products prioritized
 * Usage: [product_grid] or [product_grid count="12" grid="3"]
 */
function product_grid_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => 8,
        'grid' => 4, // Default number of products per row
    ), $atts, 'product_grid');
    
    // Get the count of products to show
    $display_count = absint($atts['count']);
    if ($display_count < 1) {
        $display_count = 8; // Fallback if an invalid number is provided
    }
    
    // Get number of products per row
    $grid_columns = absint($atts['grid']);
    if ($grid_columns < 1 || $grid_columns > 6) {
        $grid_columns = 4; // Fallback to default if invalid
    }
    
    // Get user's gender and age from meta
    $user_gender = '';
    $user_age_category = '';
    $user_categories = array();
    
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_gender = get_user_meta($user_id, 'Patient_Gender', true);
        $birth_date = get_user_meta($user_id, 'Patient_Date_of_Birth', true);
        $user_age_category = get_user_age_category($birth_date);
        
        // Get user's last orders
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Get categories from last orders
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $terms = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                $user_categories = array_merge($user_categories, $terms);
            }
        }
        $user_categories = array_unique($user_categories);
    }

    $all_products = array();
    $needed_products = $display_count;

    // First Priority: Products matching both user data AND categories
    if (!empty($user_gender) && !empty($user_age_category) && !empty($user_categories)) {
        $first_priority_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'relation' => 'AND',
                    array(
                        'taxonomy' => 'product_tag',
                        'field'    => 'name',
                        'terms'    => $user_gender,
                        'operator' => 'IN'
                    ),
                    array(
                        'taxonomy' => 'product_tag',
                        'field'    => 'name',
                        'terms'    => $user_age_category,
                        'operator' => 'IN'
                    )
                ),
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $user_categories,
                    'operator' => 'IN'
                )
            )
        );
        
        $first_priority_products = wc_get_products($first_priority_args);
        $all_products = array_slice(array_merge($all_products, $first_priority_products), 0, $needed_products);
        $needed_products = $display_count - count($all_products);
    }

    // Second Priority: Products matching user interests
    if ($needed_products > 0 && !empty($user_categories)) {
        $second_priority_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $user_categories,
                    'operator' => 'IN'
                )
            ),
            'post__not_in' => array_map(function($product) {
                return $product->get_id();
            }, $all_products)
        );
        
        $second_priority_products = wc_get_products($second_priority_args);
        $all_products = array_slice(array_merge($all_products, $second_priority_products), 0, $display_count);
        $needed_products = $display_count - count($all_products);
    }

    // Third Priority: Products matching just user data
    if ($needed_products > 0 && (!empty($user_gender) || !empty($user_age_category))) {
        $tax_query = array('relation' => 'AND');
        
        if (!empty($user_gender)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'name',
                'terms'    => $user_gender,
                'operator' => 'IN'
            );
        }
        
        if (!empty($user_age_category)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'name',
                'terms'    => $user_age_category,
                'operator' => 'IN'
            );
        }
        
        $third_priority_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => $tax_query,
            'post__not_in' => array_map(function($product) {
                return $product->get_id();
            }, $all_products)
        );
        
        $third_priority_products = wc_get_products($third_priority_args);
        $all_products = array_slice(array_merge($all_products, $third_priority_products), 0, $display_count);
        $needed_products = $display_count - count($all_products);
    }

    // Last Priority: Latest products
    if ($needed_products > 0) {
        $last_priority_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => array_map(function($product) {
                return $product->get_id();
            }, $all_products)
        );
        
        $last_priority_products = wc_get_products($last_priority_args);
        $all_products = array_slice(array_merge($all_products, $last_priority_products), 0, $display_count);
    }

    if (empty($all_products)) {
        return '';
    }

    // Ensure we only have the requested number of products
    $all_products = array_slice($all_products, 0, $display_count);

    ob_start();
    ?>
    <div class="product-grid" style="grid-template-columns: repeat(<?php echo esc_attr($grid_columns); ?>, 1fr);">
        <?php foreach ($all_products as $product) : 
            $price_data = get_pmpro_price_display($product->get_id());
            $is_member = !empty($price_data['membership_level']);
            $has_discount = $price_data['original_price'] != $price_data['member_price'];
            
            // Get product tags and categories
            $product_tags = wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names'));
            $product_categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'ids'));
            $matches_user_data = (!empty($user_gender) && in_array($user_gender, $product_tags)) || 
                                (!empty($user_age_category) && in_array($user_age_category, $product_tags));
            $matches_interests = !empty($user_categories) && !empty(array_intersect($product_categories, $user_categories));
            $matches_both = $matches_user_data && $matches_interests;

            // Get the product categories for display
            $categories = get_the_terms($product->get_id(), 'product_cat');
            $category_names = array();
            if ($categories && !is_wp_error($categories)) {
                foreach($categories as $category) {
                    $category_names[] = $category->name;
                }
            }
        ?>
            <div class="product-item <?php echo $matches_both ? 'priority-one' : ($matches_interests ? 'priority-two' : ($matches_user_data ? 'priority-three' : '')); ?>">
                <div class="product-image-wrapper">
                    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="product-image-link">
                        <?php echo $product->get_image('large'); ?>
                    </a>
                    <?php if ($matches_both) : ?>
                        <div class="priority-badge gold">Perfect Match</div>
                    <?php elseif ($matches_interests) : ?>
                        <div class="priority-badge silver">Based on Your Interests</div>
                    <?php elseif ($matches_user_data) : ?>
                        <div class="priority-badge bronze">Recommended for You</div>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <?php if (!empty($category_names)) : ?>
                        <div class="product-category">
                            <?php echo esc_html(implode(', ', $category_names)); ?>
                        </div>
                    <?php endif; ?>
                    <h3 class="product-title">
                        <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    </h3>
                    <div class="price">
                        <?php if (!$is_member || $price_data['membership_level']->ID == 1) : ?>
                            <?php if (!empty($price_data['member_price'])) : ?>
                                <span class="price"><?php echo wc_price($price_data['original_price']); ?></span>
                            <?php else : ?>
                                <span class="price"><?php echo wc_price($price_data['original_price']); ?></span>
                            <?php endif; ?>
                        <?php else : ?>
                            <?php if (!empty($price_data['member_price'])) : ?>
                                <span class="original-price"><del><?php echo wc_price($price_data['original_price']); ?></del></span>
                                <span class="member-price"><?php echo wc_price($price_data['member_price']); ?></span>
                            <?php else : ?>
                                <span class="price"><?php echo wc_price($price_data['original_price']); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                   
                    <div class="custom-add-to-cart">
                        <a href="javascript:void(0);" 
                           class="button" 
                           data-product_id="<?php echo esc_attr($product->get_id()); ?>" 
                           data-quantity="1">
                           <i class="default"></i>
                            <span class="space"></span>
                            <span>Add to cart</span>
                        </a>
                    </div>
                    <div class="buy-now">
                        <a href="<?php echo esc_url(wc_get_checkout_url() . '?buy-now=' . $product->get_id()); ?>" 
                           class="button product_type_simple" 
                           data-product_id="<?php echo esc_attr($product->get_id()); ?>" 
                           rel="nofollow">Buy now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Custom add to cart handling
        $('.custom-add-to-cart').on('click', function(e) {
            e.preventDefault();
            
            var $thisButton = $(this);
            var product_id = $thisButton.data('product_id');
            var quantity = $thisButton.data('quantity');
            
            $thisButton.addClass('is-loading');
            
            // AJAX add to cart
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'woocommerce_ajax_add_to_cart',
                    product_id: product_id,
                    quantity: quantity
                },
                success: function(response) {
                    $thisButton.removeClass('is-loading');
                    
                    if(response.error & response.product_url) {
                        window.location = response.product_url;
                        return;
                    }
                    
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisButton]);
                    
                    $thisButton.addClass('added');
                    setTimeout(function() {
                        $thisButton.removeClass('added');
                    }, 1000);
                    
                    showAddToCartNotification();
                },
                error: function() {
                    $thisButton.removeClass('is-loading');
                }
            });
        });
        
        function showAddToCartNotification() {
            var $existingNotification = $('#thegem-cart-notification');
            if ($existingNotification.length) {
                $existingNotification.remove();
            }
            
            var notificationHtml = `
               <div class="thegem-popup-notification-wrap" id="thegem-cart-notification">
                   <div class="thegem-popup-notification cart" data-timing="4000">
                       <div class="notification-message">
                           <span class="checkmark">✓</span>
                           Item added to cart
                           <span class="buttons">
                               <a class="button" href="<?php echo esc_url(wc_get_cart_url()); ?>">View Cart</a>
                               <a class="button" href="<?php echo esc_url(wc_get_checkout_url()); ?>">Checkout</a>
                           </span>
                       </div>
                   </div>
               </div>
            `;
            
            $('body').append(notificationHtml);
            
            setTimeout(function() {
                $('#thegem-cart-notification').addClass('active');
            }, 10);
            
            setTimeout(function() {
                $('#thegem-cart-notification').removeClass('active');
                setTimeout(function() {
                    $('#thegem-cart-notification').remove();
                }, 500);
            }, 4000);
        }
        
        $(document.body).off('added_to_cart.custom_grid');
        $(document.body).on('added_to_cart.custom_grid', function(e, fragments, cart_hash, $button) {
            if ($button.hasClass('custom-add-to-cart')) {
                e.stopImmediatePropagation();
            }
        });
    });
    </script>

    <style>
        /* Add some visual feedback for the add to cart button */
        .custom-add-to-cart.loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* New loading indicator that doesn't hide the button */
        .custom-add-to-cart.is-loading {
            position: relative;
            color: inherit !important;
            opacity: 0.7;
        }
        
        .custom-add-to-cart.is-loading:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: #fff;
            animation: spin 0.75s infinite linear;
            pointer-events: none;
        }
        
        @keyframes spin {
            from {transform: rotate(0deg);}
            to {transform: rotate(360deg);}
        }
        
        .custom-add-to-cart.added {
            background-color: #4CAF50 !important;
            color: white !important;
        }
        .custom-add-to-cart .default {
            font-style: normal;
            font-family: 'thegem-icons';
            font-weight: normal;
            -webkit-font-smoothing: initial;
            color: currentColor;
            font-size: 16px;
            line-height: 1;
        }
        .custom-add-to-cart i.default:before{   
            content: '\e67e';
        }
        /* Animation for added to cart */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .custom-add-to-cart.added {
            animation: pulse 0.5s ease-in-out;
        }
        
        /* Notification popup styles */
        .thegem-popup-notification-wrap {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
            width: auto;
            min-width: 300px;
            max-width: 90%;
        }
        
        .thegem-popup-notification-wrap.active {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }
        
        .thegem-popup-notification {
            background-color: #071938;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            padding: 15px 25px;
            width: 100%;
        }
        
        .thegem-popup-notification .notification-message {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            font-size: 16px;
            line-height: 1.5;
            text-align: center;
        }
        
        .thegem-popup-notification .checkmark {
            display: inline-block;
            margin-right: 10px;
            color: #ffffff;
            font-size: 18px;
        }
        
        .thegem-popup-notification .buttons {
            display: flex;
            gap: 10px;
            margin-left: 15px;
            justify-content: center;
        }
        
        .thegem-popup-notification .buttons .button {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
            border: 1px solid #ffffff;
            background: transparent;
            color: #ffffff;
        }
        
        .thegem-popup-notification .buttons .button:last-child {
            background-color: #ffffff;
            color: #0a2240;
        }
        
        .thegem-popup-notification .buttons .button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .thegem-popup-notification .notification-message {
                flex-direction: column;
            }
            
            .thegem-popup-notification .buttons {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('product_grid', 'product_grid_shortcode');

/**
 * Add AJAX add to cart handler
 */
function custom_ajax_add_to_cart_handler() {
    WC_AJAX::add_to_cart();
}
add_action('wp_ajax_woocommerce_ajax_add_to_cart', 'custom_ajax_add_to_cart_handler');
add_action('wp_ajax_nopriv_woocommerce_ajax_add_to_cart', 'custom_ajax_add_to_cart_handler');

/**
 * Add grid styles
 */
function product_grid_styles() {
    ?>
    <style>
        .product-grid {
            display: grid;
            /* grid-template-columns is now set inline via style attribute */
            gap: 42px;
            margin: 20px auto;
            justify-content: center;
            max-width: 1400px;
            width: 100%;
        }

        .product-item {
            
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            overflow: hidden;
            height: 326px;
        }

        .product-image-link {
            display: block;
            width: 100%;
            height: 100%;
        }

        .product-image-link img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
            max-width: 100%;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .product-image-link:hover img {
            transform: scale(1.05);
        }

        .product-category {
            font-family: 'Montserrat', sans-serif;
            font-style: normal;
            font-weight: 500;
            font-size: 9px;
            line-height: 10.8px;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            color: var(--thegem-to-product-grid-category-color, #99A9B5)!important;
            margin-bottom: 5px;
            transition: color 0.3s;
        }

        .product-category:hover {
            color: var(--thegem-to-product-grid-category-hover-color, #BB9A2A)!important;
        }

        .product-title {
            margin: 10px 0;
            font-family: 'Montserrat', sans-serif;
            font-style: normal;
            font-weight: 700;
            font-size: 14px;
            line-height: 18.2px;
            max-height: calc(18.2px * 2);
            overflow: hidden;
        }

        .product-title a {
            color: var(--thegem-to-product-grid-title-color, #BB9A2A) !important;
            text-decoration: none;
            transition: color 0.3s;
        }

        .product-title a:hover {
            color: var(--thegem-to-product-grid-title-hover-color, #3C3950) !important;
        }

        .product-content {
            padding: 15px;
            padding-bottom: 0;
            width: 100%;
            text-align: center;
        }

        .price {
            margin: 10px 0;
            font-family: 'Source Sans Pro', sans-serif;
            font-style: normal;
            font-weight: 300;
            font-size: 20px;
            line-height: 20px;
            color: var(--thegem-to-product-grid-price-color, #FFF);
        }
        .price .member-price{
            font-weight: 500;
        }

        .priority-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            z-index: 1;
            font-family: 'Montserrat', sans-serif;
        }

        .priority-badge.gold {
            background: #FFD700;
            color: #000 !important;
        }

        .priority-badge.silver {
            background: #C0C0C0;
            color: #000;
        }

        .priority-badge.bronze {
            background: #CD7F32;
        }

        .product-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding: 0 15px 15px;
            width: 100%;
        }
        .product-content .custom-add-to-cart {
            margin-bottom: 10px;
        }
        .custom-add-to-cart,
        .buy-now {
            flex: 1;
            margin: 0;
        }

        .custom-add-to-cart a,
        .buy-now a {
            display: inline-block;
            width: auto;
            text-align: center;
            font-family: var(--thegem-to-button-font-family, 'Plus Jakarta Sans');
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 0;
        }
        
        .product-content .custom-add-to-cart,
        .product-content .buy-now {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
        }

        .custom-add-to-cart a {
            background: var(--thegem-to-button-basic-background-color, #BB9A2A) !important;
            color:#fff !important;
            padding: 5px 16px 6px;
            height: auto;
            text-transform: capitalize;
            border: 1px solid #BB9A2A;
        }
        .custom-add-to-cart a:hover {
            background: #000 !important;
   
        }
        .buy-now a {
            background: #dfe5e8 !important;
            color: #5f727f !important;
            padding: 5px 9px 6px;
            height: auto;
            text-transform: capitalize;
        }

        .buy-now a:hover {
            background:#BB9A2A !important;
            color: #FFF !important;
        }

        .custom-add-to-cart a i.default {
            display: inline-block;
            vertical-align: middle;
        }

        .custom-add-to-cart a span.space {
            display: inline-block;
            width: 5px;
        }
    
.product-item bdi {
    color: #fff !important;
}
        .custom-add-to-cart a span {
            display: inline-block;
            vertical-align: middle;
        }

        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 576px) {
            .product-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'product_grid_styles');