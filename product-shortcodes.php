<?php
/**
 * Product Shortcodes for Membership Pricing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
 * Usage: [product_grid]
 */
function product_grid_shortcode($atts) {
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
    $needed_products = 8;

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
        $needed_products = 8 - count($all_products);
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
        $all_products = array_slice(array_merge($all_products, $second_priority_products), 0, 8);
        $needed_products = 8 - count($all_products);
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
        $all_products = array_slice(array_merge($all_products, $third_priority_products), 0, 8);
        $needed_products = 8 - count($all_products);
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
        $all_products = array_slice(array_merge($all_products, $last_priority_products), 0, 8);
    }

    if (empty($all_products)) {
        return '';
    }

    // Ensure we only have 8 products
    $all_products = array_slice($all_products, 0, 8);

    ob_start();
    ?>
    <div class="product-grid">
        <?php foreach ($all_products as $product) : 
            $price_data = get_pmpro_price_display($product->get_id());
            $is_member = !empty($price_data['membership_level']);
            $has_discount = $price_data['original_price'] != $price_data['member_price'];
            $is_subscription = is_woo_subscription_product($product->get_id());
            
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
                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
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
                        <?php if ($has_discount) : ?>
                            <span class="original-price"><del><?php echo format_price_with_subscription($price_data['original_price'], $product->get_id()); ?></del></span>
                            <span class="member-price"><?php echo format_price_with_subscription($price_data['member_price'], $product->get_id()); ?></span>
                            <?php if ($is_member) : ?>
                                <span class="member-level-note"><?php echo sprintf(__('Your %s price', 'your-text-domain'), $price_data['membership_level']->name); ?></span>
                            <?php endif; ?>
                        <?php else : ?>
                            <span class="regular-price"><?php echo format_price_with_subscription($price_data['original_price'], $product->get_id()); ?></span>
                        <?php endif; ?>
                    </div>
                   
                    <div class="add-to-cart">
                            <a href="javascript:void(0);" 
                               class="button custom-add-to-cart" 
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
            
            // Add loading class
            $thisButton.addClass('loading');
            
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
                    // Remove loading class
                    $thisButton.removeClass('loading');
                    
                    if(response.error & response.product_url) {
                        window.location = response.product_url;
                        return;
                    }
                    
                    // Trigger event so cart widget updates
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisButton]);
                    
                    // Don't change the button to "View Cart"
                    // Just add a small visual feedback and keep the button as "Add to Cart"
                    $thisButton.addClass('added');
                    setTimeout(function() {
                        $thisButton.removeClass('added');
                    }, 1000);
                },
                error: function() {
                    $thisButton.removeClass('loading');
                }
            });
        });
        
        // Remove "added_to_cart" event handlers that would change our button
        $(document.body).off('added_to_cart.custom_grid');
        $(document.body).on('added_to_cart.custom_grid', function(e, fragments, cart_hash, $button) {
            if ($button.hasClass('custom-add-to-cart')) {
                // Prevent default WooCommerce behavior
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
        
        .custom-add-to-cart.added {
            background-color: #4CAF50 !important;
            color: white !important;
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
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px auto;
            justify-content: center;
            max-width: 1400px;
            width: 100%;
        }

        .product-item {
            border: 1px solid #eee;
            background: var(--main-bg-color, #071938);
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
            aspect-ratio: 1;
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
            color: var(--thegem-to-product-grid-category-color, #99A9B5);
            margin-bottom: 5px;
            transition: color 0.3s;
        }

        .product-category:hover {
            color: var(--thegem-to-product-grid-category-hover-color, #BB9A2A);
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
            color: var(--thegem-to-product-grid-title-color, #BB9A2A);
            text-decoration: none;
            transition: color 0.3s;
        }

        .product-title a:hover {
            color: var(--thegem-to-product-grid-title-hover-color, #3C3950);
        }

        .product-content {
            padding: 15px;
            width: 100%;
            text-align: center;
        }

        .price {
            margin: 10px 0;
            font-family: 'Source Sans Pro', sans-serif;
            font-style: normal;
            font-weight: normal;
            font-size: 18px;
            line-height: 18px;
            color: var(--thegem-to-product-grid-price-color, #FFF);
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
        }

        .priority-badge.gold {
            background: #FFD700;
            color: #000;
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
        .product-content .add-to-cart {
            margin-bottom: 10px;
        }
        .add-to-cart,
        .buy-now {
            flex: 1;
            margin: 0;
        }

        .add-to-cart a,
        .buy-now a {
            display: inline-block;
            width: 100%;
            padding: 12px 20px;
            text-align: center;
            font-family: var(--thegem-to-button-font-family, 'Plus Jakarta Sans');
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            text-transform: uppercase;
            text-decoration: none;
            border-radius: 3px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 0;
        }

        .add-to-cart a {
            background: var(--thegem-to-button-basic-background-color, #BB9A2A);
            color: var(--thegem-to-button-basic-color, #000);
        }

        .buy-now a {
            background: var(--thegem-to-styled-color4, #000);
            color: var(--thegem-to-styled-color2, #FFF);
        }

        .add-to-cart a:hover {
            background: var(--thegem-to-button-basic-background-color-hover, #0D2E37);
            color: var(--thegem-to-button-basic-color-hover, #fff);
        }

        .buy-now a:hover {
            background: var(--thegem-to-styled-color3, #BB9A2A);
            color: var(--thegem-to-styled-color2, #FFF);
        }

        .add-to-cart a i.default {
            display: inline-block;
            vertical-align: middle;
        }

        .add-to-cart a span.space {
            display: inline-block;
            width: 5px;
        }

        .add-to-cart a span {
            display: inline-block;
            vertical-align: middle;
        }

        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(3, 1fr);
                max-width: 1100px;
            }
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                max-width: 800px;
            }
        }

        @media (max-width: 576px) {
            .product-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'product_grid_styles');