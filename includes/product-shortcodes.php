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
    
    if ($age < 25) return 'Youth';
    elseif ($age <= 35) return 'Young Adult';
    elseif ($age <= 50) return 'Adult';
    else return 'Mature Adult';
}

/**
 * Shortcode to display products with gender and age-specific products prioritized
 * Usage: [product_grid] or [product_grid count="12" grid="3"]
 */
function product_grid_shortcode($atts) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'count' => 8,
        'grid' => 4,
    ), $atts, 'product_grid');
    
    $display_count = max(1, absint($atts['count']));
    $grid_columns = min(6, max(1, absint($atts['grid'])));
    
    // Get user data
    $user_data = array(
        'gender' => '',
        'age_category' => '',
        'categories' => array()
    );
    
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_data['gender'] = get_user_meta($user_id, 'Patient_Gender', true);
        $birth_date = get_user_meta($user_id, 'Patient_Date_of_Birth', true);
        $user_data['age_category'] = get_user_age_category($birth_date);
        
        // Get categories from last orders
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $terms = wp_get_post_terms($item->get_product_id(), 'product_cat', array('fields' => 'ids'));
                $user_data['categories'] = array_merge($user_data['categories'], $terms);
            }
        }
        $user_data['categories'] = array_unique($user_data['categories']);
    }

    // Get products based on priority
    $all_products = get_prioritized_products($user_data, $display_count);

    if (empty($all_products)) {
        return '';
    }

    ob_start();
    ?>
    <div class="product-grid recommended-products" style="grid-template-columns: repeat(<?php echo esc_attr($grid_columns); ?>, 1fr);">
        <?php foreach ($all_products as $product) : 
            $product_id = $product->get_id();
            $price_data = get_pmpro_price_display($product_id);
            $priority_info = get_product_priority($product_id, $user_data);
        ?>
            <div class="product-item <?php echo esc_attr($priority_info['class']); ?>">
                <div class="product-image-wrapper">
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="product-image-link">
                        <?php echo $product->get_image('large'); ?>
                    </a>
                    <?php if ($priority_info['badge']) : ?>
                        <div class="priority-badge <?php echo esc_attr($priority_info['badge_class']); ?>">
                            <?php echo esc_html($priority_info['badge']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <?php 
                    $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names'));
                    if (!empty($categories)) : ?>
                        <div class="product-category">
                            <?php echo esc_html(implode(', ', $categories)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="product-title">
                        <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    </h3>
                    
                    <div class="price">
                        <?php echo get_price_html($price_data); ?>
                    </div>
                   
                    <div class="custom-add-to-cart">
                        <a href="javascript:void(0);" 
                           class="button custom-ajax-add-to-cart" 
                           data-product_id="<?php echo esc_attr($product_id); ?>" 
                           data-quantity="1">
                            <i class="default"></i>
                            <span class="space"></span>
                            <span>Add to cart</span>
                        </a>
                    </div>
                    <div class="buy-now">
                        <a href="<?php echo esc_url(add_query_arg('buy-now', $product_id, wc_get_checkout_url())); ?>" 
                           class="button">Buy now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Custom AJAX add to cart
        $(document).on('click', '.custom-ajax-add-to-cart', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var product_id = $button.data('product_id');
            var quantity = $button.data('quantity') || 1;
            
            if ($button.hasClass('loading')) return;
            
            $button.addClass('loading');
            $button.find('span:last').text('Adding...');
            
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'custom_add_to_cart',
                    product_id: product_id,
                    quantity: quantity,
                    nonce: '<?php echo wp_create_nonce("add-to-cart"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Update cart fragments
                        $(document.body).trigger('wc_fragment_refresh');
                        
                        // Show success state
                        $button.removeClass('loading').addClass('added');
                        $button.find('span:last').text('Added!');
                        
                        // Show notification
                        showCartNotification(response.data.product_name);
                        
                        // Reset button after 2 seconds
                        setTimeout(function() {
                            $button.removeClass('added');
                            $button.find('span:last').text('Add to cart');
                        }, 2000);
                    } else {
                        $button.removeClass('loading');
                        $button.find('span:last').text('Add to cart');
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    $button.removeClass('loading');
                    $button.find('span:last').text('Add to cart');
                    alert('Error adding to cart');
                }
            });
        });
        
        function showCartNotification(productName) {
            // Remove existing notification
            $('.cart-notification').remove();
            
            var notification = $('<div class="cart-notification">' +
                '<div class="notification-content">' +
                '<span class="checkmark">✓</span> ' +
                productName + ' added to cart' +
                '<div class="notification-buttons">' +
                '<a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="button view-cart">View Cart</a>' +
                '<a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="button checkout">Checkout</a>' +
                '</div></div></div>');
            
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    });
    </script>

    <style>
        .recommended-products {
            display: grid;
            gap: 42px;
            margin: 20px auto;
            justify-content: center;
            max-width: 1400px;
            width: 100%;
        }

        .recommended-products .product-item {
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .recommended-products .product-image-wrapper {
            position: relative;
            width: 100%;
            overflow: hidden;
            height: 326px;
        }

        .recommended-products .product-image-link {
            display: block;
            width: 100%;
            height: 100%;
        }

        .recommended-products .product-image-link img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .recommended-products .product-image-link:hover img {
            transform: scale(1.05);
        }

        .recommended-products .product-category {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            font-size: 9px;
            line-height: 10.8px;
            text-transform: uppercase;
            letter-spacing: 0.45px;
            color: #99A9B5;
            margin-bottom: 5px;
            transition: color 0.3s;
        }

        .recommended-products .product-category:hover {
            color: #BB9A2A;
        }

        .recommended-products .product-title {
            margin: 10px 0;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 14px;
            line-height: 18.2px;
            max-height: 36.4px;
            overflow: hidden;
        }

        .recommended-products .product-title a {
            color: #BB9A2A;
            text-decoration: none;
            transition: color 0.3s;
        }

        .recommended-products .product-title a:hover {
            color: #3C3950;
        }

        .recommended-products .product-content {
            padding: 15px 15px 0;
            width: 100%;
            text-align: center;
        }

        .recommended-products .price {
            margin: 10px 0;
            font-family: 'Source Sans Pro', sans-serif;
            font-weight: 300;
            font-size: 20px;
            line-height: 20px;
            color: #FFF;
        }
        .recommended-products label, .recommended-products bdi{
            color: #fff !important;
        }
        .recommended-products .price .member-price {
            font-weight: 500;
        }

        .recommended-products .price .original-price {
            text-decoration: line-through;
            opacity: 0.7;
            margin-right: 10px;
        }

        .recommended-products .priority-badge {
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

        .recommended-products .priority-badge.gold {
            background: #FFD700;
            color: #000;
        }

        .recommended-products .priority-badge.silver {
            background: #C0C0C0;
            color: #000;
        }

        .recommended-products .priority-badge.bronze {
            background: #CD7F32;
        }

        .recommended-products .custom-add-to-cart,
        .recommended-products .buy-now {
            margin-bottom: 10px;
        }

        .recommended-products .custom-add-to-cart a,
        .recommended-products .buy-now a {
            display: inline-block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 14px;
            line-height: 1.2;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: capitalize;
            padding: 5px 20px;
            height: unset !important;
        }

        .recommended-products .custom-add-to-cart a {
            background: #BB9A2A;
            color: #fff;
            border: 1px solid #BB9A2A;
            margin: 0px !important;
        }

        .recommended-products .custom-add-to-cart a:hover {
            background: #000;
            border-color: #000;
            color: #fff;
        }

        .recommended-products .custom-add-to-cart a.loading {
            opacity: 0.6;
            cursor: not-allowed;
            position: relative;
        }

        .recommended-products .custom-add-to-cart a.loading:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.75s infinite linear;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .recommended-products .custom-add-to-cart a.added {
            background: #4CAF50 !important;
            border-color: #4CAF50 !important;
        }

        .recommended-products .custom-add-to-cart a i.default {
            font-style: normal;
            font-family: 'thegem-icons';
            font-weight: normal;
            -webkit-font-smoothing: initial;
            font-size: 16px;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }

        .recommended-products .custom-add-to-cart a i.default:before {
            content: '\e67e';
        }

        .recommended-products .custom-add-to-cart a span.space {
            display: inline-block;
            width: 5px;
        }

        .recommended-products .buy-now a {
            background: #dfe5e8;
            color: #5f727f;
            margin: 0px !important;
        }

        .recommended-products .buy-now a:hover {
            background: #BB9A2A;
            color: #FFF;
        }

        .recommended-products .product-item bdi {
            color: #fff;
        }

        /* WooCommerce notification override */
        .recommended-products .woocommerce-message {
            background-color: #071938;
            color: #ffffff;
            border-radius: 4px;
            padding: 15px 25px;
            margin-bottom: 20px;
        }

        .recommended-products .woocommerce-message .button {
            background: #ffffff;
            color: #071938;
            border-radius: 30px;
            padding: 6px 15px;
            margin-left: 10px;
        }

        /* Cart notification styles */
        .cart-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            z-index: 99999;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .cart-notification.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        .notification-content {
            background: #071938;
            color: #fff;
            padding: 20px 30px;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }

        .notification-content .checkmark {
            color: #4CAF50;
            font-size: 20px;
            font-weight: bold;
        }

        .notification-buttons {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }

        .notification-buttons .button {
            padding: 6px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid #fff;
            background: transparent;
            color: #fff;
        }

        .notification-buttons .button.checkout {
            background: #fff;
            color: #071938;
        }

        .notification-buttons .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
    return ob_get_clean();
}
add_shortcode('product_grid', 'product_grid_shortcode');

/**
 * Get prioritized products based on user data
 */
function get_prioritized_products($user_data, $limit) {
    $products = array();
    $exclude_ids = array();
    
    // Build tax queries
    $priority_queries = array();
    
    // Priority 1: Both user data AND categories
    if (!empty($user_data['gender']) && !empty($user_data['age_category']) && !empty($user_data['categories'])) {
        $priority_queries[] = array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => array($user_data['gender'], $user_data['age_category']),
                'operator' => 'IN'
            ),
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $user_data['categories'],
                'operator' => 'IN'
            )
        );
    }
    
    // Priority 2: User categories only
    if (!empty($user_data['categories'])) {
        $priority_queries[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $user_data['categories'],
            'operator' => 'IN'
        );
    }
    
    // Priority 3: User data only
    if (!empty($user_data['gender']) || !empty($user_data['age_category'])) {
        $terms = array_filter(array($user_data['gender'], $user_data['age_category']));
        if (!empty($terms)) {
            $priority_queries[] = array(
                'taxonomy' => 'product_tag',
                'field' => 'name',
                'terms' => $terms,
                'operator' => 'IN'
            );
        }
    }
    
    // Execute queries in priority order
    foreach ($priority_queries as $tax_query) {
        if (count($products) >= $limit) break;
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit - count($products),
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array($tax_query),
            'post__not_in' => $exclude_ids
        );
        
        $query_products = wc_get_products($args);
        foreach ($query_products as $product) {
            $exclude_ids[] = $product->get_id();
        }
        $products = array_merge($products, $query_products);
    }
    
    // Fill with latest products if needed
    if (count($products) < $limit) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit - count($products),
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => $exclude_ids
        );
        
        $products = array_merge($products, wc_get_products($args));
    }
    
    return array_slice($products, 0, $limit);
}

/**
 * Get product priority information
 */
function get_product_priority($product_id, $user_data) {
    $product_tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'names'));
    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    
    $matches_user_data = (!empty($user_data['gender']) && in_array($user_data['gender'], $product_tags)) || 
                        (!empty($user_data['age_category']) && in_array($user_data['age_category'], $product_tags));
    $matches_interests = !empty($user_data['categories']) && !empty(array_intersect($product_categories, $user_data['categories']));
    
    if ($matches_user_data && $matches_interests) {
        return array(
            'class' => 'priority-one',
            'badge' => 'Perfect Match',
            'badge_class' => 'gold'
        );
    } elseif ($matches_interests) {
        return array(
            'class' => 'priority-two',
            'badge' => 'Based on Your Interests',
            'badge_class' => 'silver'
        );
    } elseif ($matches_user_data) {
        return array(
            'class' => 'priority-three',
            'badge' => 'Recommended for You',
            'badge_class' => 'bronze'
        );
    }
    
    return array('class' => '', 'badge' => '', 'badge_class' => '');
}

/**
 * Get formatted price HTML
 */
function get_price_html($price_data) {
    $is_member = !empty($price_data['membership_level']) && $price_data['membership_level']->ID != 1;
    $has_discount = !empty($price_data['member_price']) && $price_data['original_price'] != $price_data['member_price'];
    
    if ($is_member && $has_discount) {
        return sprintf(
            '<span class="original-price"><del>%s</del></span><span class="member-price">%s</span>',
            wc_price($price_data['original_price']),
            wc_price($price_data['member_price'])
        );
    }
    
    return sprintf('<span class="price">%s</span>', wc_price($price_data['original_price']));
}

/**
 * AJAX handler for custom add to cart
 */
function custom_add_to_cart_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'add-to-cart')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($product_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid product'));
        return;
    }
    
    // Get product
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(array('message' => 'Product not found'));
        return;
    }
    
    // Add to cart
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
    
    if ($cart_item_key) {
        // Get cart count and totals
        $data = array(
            'message' => 'Product added to cart',
            'product_name' => $product->get_name(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
        );
        
        wp_send_json_success($data);
    } else {
        wp_send_json_error(array('message' => 'Could not add to cart'));
    }
}
add_action('wp_ajax_custom_add_to_cart', 'custom_add_to_cart_handler');
add_action('wp_ajax_nopriv_custom_add_to_cart', 'custom_add_to_cart_handler');