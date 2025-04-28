<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display membership level options before cancel form
 * 
 * @param WP_User $current_user Current user object
 * @param array $old_level_ids Array of current level IDs
 */
function oba_display_level_options_before_cancel($current_user, $old_level_ids) {
    // Only run this code if we have a valid user
    if (empty($current_user) || !is_object($current_user) || empty($current_user->ID)) {
        return;
    }

    // Get all available membership levels
    $available_levels = pmpro_getAllLevels(true, true);
    
    // Get user's current level(s)
    $current_levels = pmpro_getMembershipLevelsForUser($current_user->ID);
    $current_level_ids = array();
    
    if (!empty($current_levels)) {
        foreach ($current_levels as $level) {
            $current_level_ids[] = $level->id;
        }
    }
    
    // If no available levels or user has no levels, exit
    if (empty($available_levels) || empty($current_level_ids)) {
        return;
    }
    
    // Get user's recent orders
    $recent_orders = array();
    $total_savings = 0;
    
    if (function_exists('wc_get_orders')) {
        $orders = wc_get_orders(array(
            'customer_id' => $current_user->ID,
            'limit' => 15,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $product = wc_get_product($product_id);
                
                if (!$product) {
                    continue;
                }
                
                $quantity = $item->get_quantity();
                $product_name = $item->get_name();
                
                // Get original and membership prices
                $price_data = array();
                if (function_exists('get_pmpro_price_display')) {
                    $price_data = get_pmpro_price_display($product_id);
                } else {
                    // Fallback if the function doesn't exist
                    $original_price = get_post_meta($product_id, '_regular_price', true);
                    $price_data = array(
                        'original_price' => $original_price,
                        'member_price' => $item->get_total() / $quantity,
                        'membership_level' => !empty($current_levels) ? $current_levels[0] : null
                    );
                }
                
                // Calculate per-item savings
                $item_original_total = $price_data['original_price'] * $quantity;
                $item_member_total = $price_data['member_price'] * $quantity;
                $item_savings = $item_original_total - $item_member_total;
                
                // Only add to the list if there were actual savings
                if ($item_savings > 0) {
                    $total_savings += $item_savings;
                    
                    $recent_orders[] = array(
                        'product_name' => $product_name,
                        'quantity' => $quantity,
                        'original_price' => $price_data['original_price'],
                        'member_price' => $price_data['member_price'],
                        'savings' => $item_savings,
                        'order_date' => $order->get_date_created()->date_i18n(get_option('date_format'))
                    );
                }
            }
        }
    }
    
    // Start output
    ?>
    <style>
        /* Custom styles for the membership cancellation page */
        .pmpro-level-switcher-container {
            margin: 30px 0;
            padding: 25px;
            background-color: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .pmpro-level-switcher-container h3 {
            color: #BB9A2A;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .pmpro-recent-purchases {
            margin-bottom: 25px;
        }
        
        .pmpro-recent-purchases h4 {
            color:#071938;
            font-size: 20px;
            margin-bottom: 15px;
        }
        
        .pmpro-savings-summary {
            font-size: 18px;
            margin-bottom: 20px !important;
            background-color: #f0f8ff;
            padding: 12px !important;
            border-left: 4px solid #BB9A2A;
            border-radius: 3px;
        }
        
        .pmpro-savings-summary strong {
            color: #BB9A2A;
            font-weight: 700;
        }
        
        .pmpro-recent-orders {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .pmpro-recent-orders th {
            background-color: #f2f2f2;
            padding: 10px 15px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: 600;
        }
        
        .pmpro-recent-orders td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .pmpro-recent-orders tr:hover {
            background-color: #f5f5f5;
        }
        
        .pmpro-warning-message {
            color:#BB9A2A;
            font-size: 16px;
            padding: 15px;
            background-color: #fff5f5;
            border-left: 4px solid #BB9A2Af;
            margin-top: 20px;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .pmpro-cancel-continue {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .pmpro-cancel-continue p {
            font-style: italic;
            color: #666;
        }
    </style>
    
    <div class="pmpro-level-switcher-container">
        <h3><?php _e('Before You Cancel Your Membership'); ?></h3>
        
        <?php if (!empty($recent_orders)) : ?>
            <div class="pmpro-recent-purchases">
                <h4><?php _e('Your Recent Purchases with Membership Savings'); ?></h4>
                <p class="pmpro-savings-summary"><?php printf(__('You\'ve saved a total of %s with your membership!'), '<strong>' . wc_price($total_savings) . '</strong>'); ?></p>
                
                <table class="pmpro-recent-orders">
                    <thead>
                        <tr>
                            <th><?php _e('Product'); ?></th>
                            <th><?php _e('Regular Price'); ?></th>
                            <th><?php _e('Member Price'); ?></th>
                            <th><?php _e('You Saved'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order) : ?>
                            <tr>
                                <td><?php echo esc_html($order['product_name']); ?></td>
                                <td><?php echo wc_price($order['original_price']); ?></td>
                                <td><?php echo wc_price($order['member_price']); ?></td>
                                <td><?php echo wc_price($order['savings']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="pmpro-warning-message">
                    <?php _e('By cancelling your membership, you will lose these savings on future purchases.'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="pmpro-cancel-continue">
            <p><?php _e('If you still want to cancel your membership, please continue with the cancellation form below.'); ?></p>
        </div>
    </div>
    <?php
}

// Hook to display level options before cancel form
add_action('pmpro_cancel_before_submit', 'oba_display_level_options_before_cancel', 10, 2); 