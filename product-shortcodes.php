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
    $needed_products = 6;

    // First Priority: Products matching both user data AND categories
    if (!empty($user_gender) && !empty($user_age_category) && !empty($user_categories)) {
        $first_priority_args = array(
            'post_type' => 'product',
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
        $all_products = array_merge($all_products, $first_priority_products);
        $needed_products = 6 - count($all_products);
    }

    // Second Priority: Products matching user interests (categories)
    if ($needed_products > 0 && !empty($user_categories)) {
        $second_priority_args = array(
            'post_type' => 'product',
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
        $all_products = array_merge($all_products, $second_priority_products);
        $needed_products = 6 - count($all_products);
    }

    // Third Priority: Products matching just user data (gender/age)
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
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => $tax_query,
            'post__not_in' => array_map(function($product) {
                return $product->get_id();
            }, $all_products)
        );
        
        $third_priority_products = wc_get_products($third_priority_args);
        $all_products = array_merge($all_products, $third_priority_products);
        $needed_products = 6 - count($all_products);
    }

    // Last Priority: Latest products
    if ($needed_products > 0) {
        $last_priority_args = array(
            'post_type' => 'product',
            'posts_per_page' => $needed_products,
            'orderby' => 'date',
            'order' => 'DESC',
            'post__not_in' => array_map(function($product) {
                return $product->get_id();
            }, $all_products)
        );
        
        $last_priority_products = wc_get_products($last_priority_args);
        $all_products = array_merge($all_products, $last_priority_products);
    }

    if (empty($all_products)) {
        return '';
    }

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
        ?>
            <div class="product-item <?php echo $matches_both ? 'priority-one' : ($matches_interests ? 'priority-two' : ($matches_user_data ? 'priority-three' : '')); ?>">
                <div class="product-image">
                    <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                </div>
                <div class="product-content">
                    <h3><?php echo esc_html($product->get_name()); ?></h3>
                    <?php if ($matches_both) : ?>
                        <div class="priority-badge gold">Perfect Match</div>
                    <?php elseif ($matches_interests) : ?>
                        <div class="priority-badge silver">Based on Your Interests</div>
                    <?php elseif ($matches_user_data) : ?>
                        <div class="priority-badge bronze">Recommended for You</div>
                    <?php endif; ?>
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
                        <?php woocommerce_template_loop_add_to_cart(); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('product_grid', 'product_grid_shortcode');

/**
 * Add grid styles
 */
function product_grid_styles() {
    ?>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        .product-item {
            border: 1px solid #eee;
            padding: 15px;
            text-align: center;
            position: relative;
        }
        .product-item.priority-one {
            border: 2px solid #FFD700;
        }
        .product-item.priority-two {
            border: 2px solid #C0C0C0;
        }
        .product-item.priority-three {
            border: 2px solid #CD7F32;
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
        .product-image img {
            max-width: 100%;
            height: auto;
        }
        .product-content h3 {
            margin: 10px 0;
            font-size: 16px;
        }
        .price {
            margin: 10px 0;
        }
        .original-price {
            text-decoration: line-through;
            color: #999;
            margin-right: 10px;
        }
        .member-price {
            font-weight: bold;
            color: #bb9a2a;
        }
        .member-level-note {
            display: block;
            font-size: 0.8em;
            color: #555;
            margin-top: 3px;
        }
        .subscription-suffix {
            font-size: 0.85em;
            font-weight: normal;
            color: #666;
        }
        .add-to-cart {
            margin-top: 10px;
        }
        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 576px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'product_grid_styles'); 