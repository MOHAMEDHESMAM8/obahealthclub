/**
 * Display original price and membership price for Paid Memberships Pro
 * Optimized version with cart total original price display in cart totals table
 */

// Function to get the original and membership price
function get_pmpro_price_display($product_id) {
    // Get the original price of the product
    $original_price = get_post_meta($product_id, '_regular_price', true);
    
    // Check if user is logged in with membership
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $membership_level = pmpro_getMembershipLevelForUser($user_id);
        
        if (!empty($membership_level)) {
            // Get membership price for this product
            $member_price = get_post_meta($product_id, '_level_' . $membership_level->ID . '_price', true);
            
            if (empty($member_price)) {
                // Apply discount if no specific price is set
                $discount = get_option('pmpro_level_' . $membership_level->ID . '_discount', 0);
                $member_price = $discount > 0 ? $original_price * (1 - ($discount / 100)) : $original_price;
            }
            
            return array(
                'original_price' => $original_price,
                'member_price' => $member_price,
                'membership_level' => $membership_level
            );
        }
    }
    
    return array(
        'original_price' => $original_price,
        'member_price' => $original_price,
        'membership_level' => null
    );
}

// Function to check if product is a subscription
function is_woo_subscription_product($product_id) {
    return get_post_meta($product_id, '_wps_sfw_product', true) == "yes";
}

// Format price with subscription suffix if applicable
function format_price_with_subscription($price, $product_id) {
    $formatted_price = wc_price($price);
    
    if (is_woo_subscription_product($product_id)) {
        $formatted_price .= '<span class="subscription-suffix">/month</span>';
    }
    return $formatted_price;
}

// Display the price with original and member price (for product listings/loop)
function display_pmpro_simple_price() {
    global $product;
    
    if (!$product) return;
    
    $price_data = get_pmpro_price_display($product->get_id());
    
    echo '<div class="pmpro-price-display">';
    
    if ($price_data['original_price'] != $price_data['member_price']) {
        echo '<div class="price-section">';
        echo '<span class="original-price"><del>' . format_price_with_subscription($price_data['original_price'], $product->get_id()) . '</del></span>';
        echo '<span class="member-price">' . format_price_with_subscription($price_data['member_price'], $product->get_id()) . '</span>';
        echo '</div>';
    } else {
        echo '<div class="price-section">';
        echo '<span class="price">' . format_price_with_subscription($price_data['original_price'], $product->get_id()) . '</span>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Display the full price table with membership options (for single product page)
function display_pmpro_full_price_table() {
    global $product;
    
    if (!$product) return;
    
    $price_data = get_pmpro_price_display($product->get_id());
    
    // Start output buffer to allow conditional display
    ob_start();
    
    echo '<div class="pmpro-price-display">';
    
    if ($price_data['original_price'] != $price_data['member_price']) {
        echo '<div class="price-section">';
        echo '<span class="original-price"><del>' . format_price_with_subscription($price_data['original_price'], $product->get_id()) . '</del></span>';
        echo '<span class="member-price">' . format_price_with_subscription($price_data['member_price'], $product->get_id()) . '</span>';
        if ($price_data['membership_level'] && !empty($price_data['membership_level']->name)) {
            echo '<span class="member-level-note">' . sprintf(__('Your %s price', 'your-text-domain'), $price_data['membership_level']->name) . '</span>';
        }
        echo '</div>';
    } else {
        echo '<div class="price-section">';
        echo '<span class="price">' . format_price_with_subscription($price_data['original_price'], $product->get_id()) . '</span>';
        echo '</div>';
    }
    
    // Show available membership levels for price savings
    $current_level_id = $price_data['membership_level'] ? $price_data['membership_level']->ID : 0;
    $group_id = 1;
    $levels_in_group = pmpro_get_level_ids_for_group($group_id);
    $available_levels = pmpro_getAllLevels(true, true);
    
    $filtered_levels = array();
    $has_valid_levels = false;
    
    foreach ($available_levels as $level) {
        if (in_array($level->id, $levels_in_group) && $level->id > $current_level_id) {
            $level_price = get_post_meta($product->get_id(), '_level_' . $level->id . '_price', true);
            
            if (empty($level_price)) {
                $discount = get_option('pmpro_level_' . $level->id . '_discount', 0);
                $level_price = $discount > 0 ? $price_data['original_price'] * (1 - ($discount / 100)) : $price_data['original_price'];
            }
            
            // Verify the price is reasonable (not zero or negative)
            $is_valid_price = (is_numeric($level_price) && $level_price > 0);
            
            // Only add levels with valid names and valid prices
            if (!empty($level->name) && $is_valid_price) {
                $filtered_levels[$level->id] = array(
                    'level' => $level,
                    'price' => $level_price
                );
                $has_valid_levels = true;
            }
        }
    }
    
    if ($has_valid_levels && !empty($filtered_levels)) {
        echo '<div class="other-membership-prices">';
        echo '<p class="other-prices-heading">Save More as a Member</p>';
        
        foreach ($filtered_levels as $level_data) {
            $level = $level_data['level'];
            $level_price = $level_data['price'];
            
            echo '<div class="membership-price-option">';
            echo '<span class="level-name">' . esc_html($level->name) . ': </span>';
            echo '<span class="level-price">' . format_price_with_subscription($level_price, $product->get_id()) . '</span>';
            echo '<a href="' . esc_url(pmpro_url('checkout', '?level=' . $level->id)) . '" class="wpcbn-btn wpcbn-btn-single wpcbn-btn-simple button alt gem-button gem-wc-button custom-get-btn">Get this membership</a>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    
    // Get the output buffer content
    $output = ob_get_clean();
    
    // Only display the content if there are valid membership levels to show or a current membership
    if ($has_valid_levels || ($price_data['membership_level'] && !empty($price_data['membership_level']->name))) {
        echo $output;
    }
}

// Calculate the original cart total without member discounts
function calculate_original_cart_total() {
    $total = 0;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $original_price = get_post_meta($product_id, '_regular_price', true);
        $total += $original_price * $quantity;
    }
    
    return $total;
}

// Calculate the member cart total with discounts
function calculate_member_cart_total() {
    if (!is_user_logged_in()) {
        return WC()->cart->get_subtotal();
    }
    
    $membership_level = pmpro_getMembershipLevelForUser(get_current_user_id());
    if (empty($membership_level)) {
        return WC()->cart->get_subtotal();
    }
    
    $total = 0;
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $price_data = get_pmpro_price_display($product_id);
        $total += $price_data['member_price'] * $quantity;
    }
    
    return $total;
}

// Modify cart item subtotal display
function pmpro_modify_cart_item_subtotal_display($subtotal, $cart_item, $cart_item_key) {
    if (!is_user_logged_in()) {
        return $subtotal;
    }
    
    $membership_level = pmpro_getMembershipLevelForUser(get_current_user_id());
    if (empty($membership_level)) {
        return $subtotal;
    }
    
    $product_id = $cart_item['product_id'];
    $price_data = get_pmpro_price_display($product_id);
    
    if ($price_data['original_price'] != $price_data['member_price']) {
        $quantity = $cart_item['quantity'];
        $original_subtotal = wc_price($price_data['original_price'] * $quantity);
        $member_subtotal = wc_price($price_data['member_price'] * $quantity);
        
        $subtotal = '<span class="pmpro-cart-price">';
        $subtotal .= '<span class="pmpro-original-price" style="text-decoration: line-through;">' . $original_subtotal . '</span> ';
        $subtotal .= '<span class="pmpro-member-price">' . $member_subtotal . '</span>';
        $subtotal .= '<br><small class="pmpro-discount-note">(' . $membership_level->name . ' discount)</small>';
        $subtotal .= '</span>';
    }
    
    return $subtotal;
}

// Add original price row in cart totals table
function pmpro_add_original_price_row_to_cart_totals($cart) {
    if (!is_user_logged_in()) {
        return;
    }
    
    $membership_level = pmpro_getMembershipLevelForUser(get_current_user_id());
    if (empty($membership_level)) {
        return;
    }
    
    $original_total = calculate_original_cart_total();
    $member_total = calculate_member_cart_total();
    
    if ($original_total != $member_total) {
        $savings = $original_total - $member_total;
        $savings_percentage = ($savings / $original_total) * 100;
        
        ?>
        <tr class="cart-original-total">
            <th>Total</th>
            <td data-title="Total">
                <span class="original-total-amount"><?php echo wc_price($original_total); ?></span>
				<span class="member-savings-amount"><?php echo wc_price($savings); ?> (<?php echo round($savings_percentage, 1); ?>%)</span>
            </td>
        </tr>
      
        <?php
    }
}

// Modify the cart totals display
function pmpro_adjust_cart_subtotal($cart) {
    if (!is_user_logged_in()) {
        return;
    }
    
    $membership_level = pmpro_getMembershipLevelForUser(get_current_user_id());
    if (empty($membership_level)) {
        return;
    }
    
    // Set cart subtotal to member price
    $member_total = calculate_member_cart_total();
    if (property_exists($cart, 'subtotal')) {
        $cart->subtotal = $member_total;
    }
}

// Add cart savings notice
function pmpro_add_cart_savings_notice() {
    if (!is_cart() && !is_checkout()) {
        return;
    }
    
    if (!is_user_logged_in()) {
        return;
    }
    
    $membership_level = pmpro_getMembershipLevelForUser(get_current_user_id());
    if (empty($membership_level)) {
        return;
    }
    
    $original_total = calculate_original_cart_total();
    $member_total = calculate_member_cart_total();
    
    if ($original_total > $member_total) {
        $savings = $original_total - $member_total;
        $savings_percentage = ($savings / $original_total) * 100;
        
        wc_add_notice(
            '<div class="membership-note">
                <strong>Membership Savings:</strong> You\'re saving ' . wc_price($savings) . ' (' . round($savings_percentage, 1) . '%) with your ' . $membership_level->name . ' membership!
            </div>',
            'notice'
        );
    }
}

// Add CSS styles
function pmpro_price_display_css() {
    ?>
    <style>
        .pmpro-price-display { margin-bottom: 20px; }
        .original-price { text-decoration: line-through; color: #999; margin-right: 10px; }
        .member-price { font-weight: bold; color: #0f834d; }
        .subscription-suffix { font-size: 0.85em; font-weight: normal; color:#fff !important; }
        .member-level-note { display: block; font-size: 0.8em; color: #555; margin-top: 3px; }
        .other-membership-prices { border-top: 1px solid #eee; padding-top: 10px; font-size: 0.9em; }
        .other-prices-heading { font-weight: bold; margin-bottom: 8px; }
        .membership-price-option { display: flex; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
        .level-name { width: 40%; font-weight: 500; }
        .level-price { width: 20%; color: #0f834d; }
        .custom-get-btn { color: #000 !important; }
        .custom-get-btn:hover { color:#fff; }
		tbody th { color:#000 !important; }

        .member-price bdi, .price .amount bdi, .original-price .amount bdi { color:#fff !important; }
        .price-wrap { display:none !important; }
        .pmpro-original-price { color: #999; font-size: 0.9em; text-decoration: line-through; }
        .pmpro-member-price { font-weight: bold; color: #0f834d; }
        .pmpro-discount-note { color: #0f834d; display: inline-block; margin-top: 3px; font-size: 0.8em; }
        body.woocommerce-page .woocommerce-info { background-color: #bb9a2a; }
        .membership-note { background-color: #bb9a2a; }
        .cart-discount-total { display: flex; flex-direction: column; }
        .cart-original-total { color: #999; }
        .original-total-amount { text-decoration: line-through; }
        .cart-savings { color: #0f834d; }
        .member-savings-amount { font-weight: bold; color: #0f834d; }
        .member-savings-note { font-size: 0.45em; color: #0f834d; margin-top: 3px; }
		
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .membership-price-option { margin-bottom: 15px; }
            .level-name, .level-price { width: 100%; margin-bottom: 5px; }
        }
    </style>
    <?php
}

// Hook functions
add_action('woocommerce_single_product_summary', 'display_pmpro_full_price_table', 11);
add_action('woocommerce_after_shop_loop_item_title', 'display_pmpro_simple_price', 11);
add_filter('woocommerce_cart_item_subtotal', 'pmpro_modify_cart_item_subtotal_display', 10, 3);

add_action('wp_head', 'pmpro_price_display_css');

// Remove default price display to avoid duplication
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);