<?php
/**
 * Display membership pricing for products
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Function to get the price display data
function get_pmpro_price_display($product_id) {
    $product = wc_get_product($product_id);
    $membership_price = get_post_meta($product_id, '_membership_price', true);
    
    // Get the original price based on product type
    if ($product && $product->is_type('variable')) {
        // For variable products, get min and max prices
        $min_price = $product->get_variation_price('min');
        $max_price = $product->get_variation_price('max');
        
        if ($min_price == $max_price) {
            $original_price = $min_price;
        } else {
            $original_price = $min_price . ' - ' . $max_price;
        }
    } else {
        // For simple products, get regular price
        $original_price = $product ? $product->get_regular_price() : get_post_meta($product_id, '_regular_price', true);
    }
    
    // Check if user is logged in with membership
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $membership_level = pmpro_getMembershipLevelForUser($user_id);
        
        return array(
            'original_price' => $original_price,
            'member_price' => $membership_price,
            'membership_level' => $membership_level,
            'is_variable' => $product && $product->is_type('variable')
        );
    }
    
    return array(
        'original_price' => $original_price,
        'member_price' => $membership_price,
        'membership_level' => null,
        'is_variable' => $product && $product->is_type('variable')
    );
}



// Split into two functions - one for list view and one for single product
function display_pmpro_price_list() {
    global $product;
    
    if (!$product) return;
    
    $price_data = get_pmpro_price_display($product->get_id());
    
    // If membership price exists, show the appropriate display
    if (!empty($price_data['member_price'])) {
        // If user has no membership or level 1
        if (!$price_data['membership_level'] || $price_data['membership_level']->ID == 1) {
            echo '<div class="membership-label-list non-member">';
            echo '<a href="https://obahealthclub.com/membership-levels/">Membership: ' . wc_price($price_data['member_price']) . '</a>';
            echo '</div>';
        }
        // If user has level 2 or higher
        else if ($price_data['membership_level']->ID >= 2) {
            echo '<div class="membership-label-list member">Membership Price</div>';
        }
    }
}

// Function to display price in list view
function display_pmpro_price_list_price() {
    global $product;
    
    if (!$product) return;
    
    $price_data = get_pmpro_price_display($product->get_id());
    
    // Format price display for variable products
    if ($price_data['is_variable']) {
        $formatted_original_price = $price_data['original_price'];
        if (strpos($formatted_original_price, ' - ') !== false) {
            // Range format: "min - max"
            $prices = explode(' - ', $formatted_original_price);
            $formatted_original_price = wc_price($prices[0]) . ' - ' . wc_price($prices[1]);
        } else {
            $formatted_original_price = wc_price($formatted_original_price);
        }
    } else {
        $formatted_original_price = wc_price($price_data['original_price']);
    }
    
    // If user has level 2 or higher and a membership price exists
    if ($price_data['membership_level'] && $price_data['membership_level']->ID >= 2 && !empty($price_data['member_price'])) {
        echo '<span class="price-custom"><del>' . $formatted_original_price . '</del> ' . wc_price($price_data['member_price']) . '</span>';
    } else {
        // For non-members or products without membership pricing
        echo '<span class="price-custom">' . $formatted_original_price . '</span>';
    }
}

function display_pmpro_price_single() {
    global $product;
    
    if (!$product) return;
    
    $price_data = get_pmpro_price_display($product->get_id());
    
    // Format price display for variable products
    if ($price_data['is_variable']) {
        $formatted_original_price = $price_data['original_price'];
        if (strpos($formatted_original_price, ' - ') !== false) {
            // Range format: "min - max"
            $prices = explode(' - ', $formatted_original_price);
            $formatted_original_price = wc_price($prices[0]) . ' - ' . wc_price($prices[1]);
        } else {
            $formatted_original_price = wc_price($formatted_original_price);
        }
    } else {
        $formatted_original_price = wc_price($price_data['original_price']);
    }
    
    echo '<div class="pmpro-price-display">';
    
    // If user has no membership or level 1
    if (!$price_data['membership_level'] || $price_data['membership_level']->ID == 1) {
        echo '<div class="price-section">';
        echo '<span class="price">' . $formatted_original_price . '</span>';
        echo '</div>';
        
        if (!empty($price_data['member_price'])) {
            echo '<div class="membership-notice">';
            echo '<p>Get this product for ' . wc_price($price_data['member_price']) . ' with a paid membership! (Our partner pharmacy pre-negotiated price)</p>';
            echo '<a href="https://obahealthclub.com/membership-levels/" class="button membership-button" target="_blank">Get Membership</a>';
            echo '</div>';
        }
    } 
    // If user has level 2 or higher
    else if ($price_data['membership_level']->ID >= 2 && !empty($price_data['member_price'])) {
        // Only show the crossed-out price and member price if a membership price exists
        echo '<div class="price-section">';
        echo '<span class="original-price"><del>' . $formatted_original_price . '</del></span>';
        echo '<span class="member-price">' . wc_price($price_data['member_price']) . '</span>';
        echo '</div>';
        echo '<p class="membership-discount">Member Exclusive! 🌟 Enjoy your special member pricing.</p>';
    } else {
        // If no membership price is set, just show the regular price
        echo '<div class="price-section">';
        echo '<span class="price">' . $formatted_original_price . '</span>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Add membership price field to product
function add_membership_price_field() {
    woocommerce_wp_text_input(
        array(
            'id' => '_membership_price',
            'label' => __('Membership Price', 'woocommerce'),
            'description' => __('Price for paid members (levels 2, 3, 4)', 'woocommerce'),
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        )
    );
}

// Save membership price
function save_membership_price_field($post_id) {
    $membership_price = isset($_POST['_membership_price']) ? $_POST['_membership_price'] : '';
    
    // Check if this is a variable product
    $product = wc_get_product($post_id);
    if ($product && $product->is_type('variable')) {
        // For variable products, get the _level_2_price and save it as _membership_price
        $level_2_price = get_post_meta($post_id, '_level_2_price', true);
        $membership_price = $level_2_price;
    }
    
    // Save even if empty (to allow clearing the field)
    update_post_meta($post_id, '_membership_price', esc_attr($membership_price));
    
    // Set this price for all membership levels (2, 3, 4, 5, 6)
    if (!empty($membership_price)) {
        for ($level = 2; $level <= 6; $level++) {
            update_post_meta($post_id, '_level_' . $level . '_price', $membership_price);
        }
    }
}

// Add CSS styles
function pmpro_price_display_css() {
    ?>
    <style>
        .pmpro-price-display { 
            margin-bottom: 20px;
        }
        .price-section { 
            margin-bottom: 10px;
        }
        .original-price { 
            text-decoration: line-through; 
            color: #999; 
            margin-right: 10px; 
        }
        .member-price { 
            font-weight: 500; 
            color: #bb9a2a; 
        }
        .price-section * {
            font-family: 'Source Sans Pro', sans-serif;
            font-style: normal;
            font-weight: 300;
            font-size: 28px;
            line-height: 28px;
        }
        .woocommerce_after_shop_loop_item_title .price-custom bdi{
            font-size: 18px !important;
            line-height: 18px !important;
            font-family: 'Source Sans Pro', sans-serif !important;
            font-style: normal !important;
            font-weight: 300 !important;
            color: #fff !important;
        }
        /* Styles for list view labels */
        .membership-label-list {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            z-index: 1;
        }
        
        .membership-label-list.non-member {
            background-color: #f8f8f8;
            border: 1px solid #bb9a2a;
            font-size: 11px;

        }
        
        .membership-label-list.non-member a {
            color: #bb9a2a;
            text-decoration: none;
        }
        
        .membership-label-list.member {
            background-color: #bb9a2a;
            color: white;
            font-size: 11px;
        }

        .membership-notice { 
            margin-top: 10px;
            padding: 10px;
            border-left: 4px solid #bb9a2a;
            display: flex;
            align-items: center;
        }
        .membership-notice p { 
            margin: 0 !important;
        }
        .membership-button {
            display: inline-block;
            margin: 0 !important;
            background-color: #bb9a2a;
            color: #fff !important;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .membership-button:hover {
            background-color: #a8871a;
        }

        /* Product list price styles */
        .woocommerce ul.products li.product .price del {
            display: inline-block;
            margin-right: 5px;
            opacity: 0.7;
        }
        
        .woocommerce ul.products li.product .price {
            color: #bb9a2a;
            font-weight: 500;
        }

        /* Responsive styles */
        @media screen and (max-width: 768px) {
            .membership-label-list {
                font-size: 12px;
                padding: 3px 8px;
            }
        }
        .list-right .product-price{
            display: none;
        }
    </style>
    <?php
}

// ✅ Add custom fields to CSV export columns
function add_custom_fields_to_export_columns($columns) {
    $columns['membership_price']   = 'Membership Price';
    $columns['usa_only_product']   = 'USA Only Product';
    $columns['extra_cost']         = 'Extra Cost';
    $columns['extra_cost_label']   = 'Extra Cost Label';
    $columns['extra_cost_taxable'] = 'Extra Cost Taxable';
    return $columns;
}

// Export: Membership Price
function export_membership_price($value, $product) {
    return get_post_meta($product->get_id(), '_membership_price', true);
}
add_filter('woocommerce_product_export_product_column_membership_price', 'export_membership_price', 10, 2);

// Export: USA Only Product
function export_usa_only_product($value, $product) {
    $val = get_post_meta($product->get_id(), '_usa_only_product', true);
    return $val === 'yes' || $val == 1 ? 'yes' : 'no';
}
add_filter('woocommerce_product_export_product_column_usa_only_product', 'export_usa_only_product', 10, 2);

// Export: Extra Cost
function export_extra_cost($value, $product) {
    return get_post_meta($product->get_id(), '_extra_cost', true);
}
add_filter('woocommerce_product_export_product_column_extra_cost', 'export_extra_cost', 10, 2);

// Export: Extra Cost Label
function export_extra_cost_label($value, $product) {
    return get_post_meta($product->get_id(), '_extra_cost_label', true);
}
add_filter('woocommerce_product_export_product_column_extra_cost_label', 'export_extra_cost_label', 10, 2);

// Export: Extra Cost Taxable
function export_extra_cost_taxable($value, $product) {
    $val = get_post_meta($product->get_id(), '_extra_cost_taxable', true);
    return $val === 'yes' || $val == 1 ? 'yes' : 'no';
}
add_filter('woocommerce_product_export_product_column_extra_cost_taxable', 'export_extra_cost_taxable', 10, 2);


// ✅ Process imported data (save to meta)
function process_imported_custom_fields($product, $data) {
    // Membership price
    if (isset($data['membership_price'])) {
        $membership_price = $data['membership_price'];
        $product->update_meta_data('_membership_price', $membership_price);

        // Also set for levels 2,3,4
        if (!empty($membership_price)) {
            for ($level = 2; $level <= 4; $level++) {
                $product->update_meta_data('_level_' . $level . '_price', $membership_price);
            }
        }
    }

    // USA Only Product
    if (isset($data['usa_only_product'])) {
        $product->update_meta_data('_usa_only_product', $data['usa_only_product']);
    }

    // Extra Cost
    if (isset($data['extra_cost'])) {
        $product->update_meta_data('_extra_cost', $data['extra_cost']);
    }

    // Extra Cost Label
    if (isset($data['extra_cost_label'])) {
        $product->update_meta_data('_extra_cost_label', $data['extra_cost_label']);
    }

    // Extra Cost Taxable
    if (isset($data['extra_cost_taxable'])) {
        $product->update_meta_data('_extra_cost_taxable', $data['extra_cost_taxable']);
    }

    return $product;
}

// ✅ Map CSV headers to custom fields
function map_custom_fields_csv_columns($columns) {
    $columns['membership_price']   = 'Membership Price';
    $columns['usa_only_product']   = 'USA Only Product';
    $columns['extra_cost']         = 'Extra Cost';
    $columns['extra_cost_label']   = 'Extra Cost Label';
    $columns['extra_cost_taxable'] = 'Extra Cost Taxable';
    return $columns;
}

// Add CSV export/import hooks
// ✅ CSV Export: add new columns
add_filter('woocommerce_product_export_column_names', 'add_custom_fields_to_export_columns');
add_filter('woocommerce_product_export_product_default_columns', 'add_custom_fields_to_export_columns');

// ✅ CSV Export: populate data for custom columns
add_filter('woocommerce_product_export_product_column', 'add_custom_fields_to_export_data', 10, 3);

// ✅ CSV Import: process incoming data before saving product
add_filter('woocommerce_product_import_pre_insert_product_object', 'process_imported_custom_fields', 10, 2);

// ✅ CSV Import: map custom column headers
add_filter('woocommerce_csv_product_import_mapping_options', 'map_custom_fields_csv_columns');
add_filter('woocommerce_csv_product_import_mapping_default_columns', 'map_custom_fields_csv_columns');


// Hook functions - Update to use different functions for list and single product
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);

add_action('woocommerce_single_product_summary', 'display_pmpro_price_single', 11);
add_action('woocommerce_before_shop_loop_item_title', 'display_pmpro_price_list', 11); // Show label before title
add_action('woocommerce_after_shop_loop_item_title', 'display_pmpro_price_list_price', 10); // Show price after title
add_action('wp_head', 'pmpro_price_display_css');

// Add membership price field to product data tabs
add_action('woocommerce_product_options_pricing', 'add_membership_price_field');
add_action('woocommerce_process_product_meta', 'save_membership_price_field');

// Function to apply membership pricing in cart
function apply_membership_pricing_to_cart($cart_object) {
    if (!is_user_logged_in()) return;
    
    $user_id = get_current_user_id();
    $membership_level = pmpro_getMembershipLevelForUser($user_id);
    
    // Only apply discount for level 2 and above
    if (!$membership_level || $membership_level->ID < 2) return;
    
    foreach ($cart_object->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $membership_price = get_post_meta($product_id, '_membership_price', true);
        
        if (!empty($membership_price)) {
            // Store the original price for reference
            $original_price = get_post_meta($product_id, '_regular_price', true);
            // Save the original price as cart item data
            $cart_object->cart_contents[$cart_item_key]['original_price'] = $original_price;
            // Apply the membership price
            $cart_item['data']->set_price($membership_price);
        }
    }
}

function display_original_price_in_cart_price($price, $cart_item, $cart_item_key) {
    // Only proceed if we're a member with discount and have original price saved
    if (is_user_logged_in() && isset($cart_item['original_price'])) {
        $user_id = get_current_user_id();
        $membership_level = pmpro_getMembershipLevelForUser($user_id);
        
        if ($membership_level && $membership_level->ID >= 2) {
            $original_price = $cart_item['original_price'];
            $original_price_formatted = wc_price($original_price);
            
            $return_price = '<span class="cart-original-price"><del>' . $original_price_formatted . '</del></span> ' . $price;
            return $return_price;
        }
    }
    
    return $price;
}

function display_original_price_in_cart_subtotal($price, $cart_item, $cart_item_key) {
    // Only proceed if we're a member with discount and have original price saved
    if (is_user_logged_in() && isset($cart_item['original_price'])) {
        $user_id = get_current_user_id();
        $membership_level = pmpro_getMembershipLevelForUser($user_id);
        
        if ($membership_level && $membership_level->ID >= 2) {
            $original_price = $cart_item['original_price'];
            
            $quantity = $cart_item['quantity'];

            $original_price = (float) $cart_item['original_price'];
            $quantity       = (int) $cart_item['quantity'];

            $original_price_formatted = wc_price($original_price * $quantity);

            $return_price = '<span class="cart-original-price"><del>' . $original_price_formatted . '</del></span> ' . $price;
            return $return_price . '<br><span class="member-discount-note">Member Discount Applied</span>';
        }
    }
    
    return $price;
}

// Function to display membership notice in cart/checkout for non-members and level 1
function display_membership_notice_in_cart($item_name, $cart_item, $cart_item_key) {
    // We'll use the price filter instead to show the notice under the price
    return $item_name;
}

// Function to add membership notice after price
function add_membership_notice_after_price($price_html, $cart_item, $cart_item_key) {
    // Check if user is logged in
    $has_membership = false;
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $membership_level = pmpro_getMembershipLevelForUser($user_id);
        // Has membership level 2 or higher
        if ($membership_level && $membership_level->ID >= 2) {
            $has_membership = true;
        }
    }
    
    // Only show notice for guests or level 1 members
    if (!$has_membership) {
        $product_id = $cart_item['product_id'];
        $membership_price = get_post_meta($product_id, '_membership_price', true);
        
        // If there's a membership price available
        if (!empty($membership_price)) {
            $formatted_price = wc_price($membership_price);
            
            $price_html .= '<div class="membership-cart-notice"><span>' . $formatted_price . ' with <a href="https://obahealthclub.com/membership-levels/" target="_blank">paid membership</a></span></div>';
        }
    }
    
    return $price_html;
}

// Add CSS for cart display
function add_cart_price_css() {
    if (is_cart() || is_checkout()) {
    ?>
    <style>
        .cart-original-price {
            opacity: 0.7;
            margin-right: 5px;
        }
        .member-discount-note {
            font-size: 0.8em;
            color: #bb9a2a;
            display: block;
            margin-top: 3px;
            font-style: italic;
        }
    </style>
    <?php
    }
}

// Add CSS for cart notice display
function add_cart_notice_css() {
    if (is_cart() || is_checkout()) {
    ?>
    <style>
        .membership-cart-notice {
            font-size: 0.65em;
            color: #bb9a2a;
            margin-top: 5px;
            font-style: italic;
        }
        .membership-cart-notice a {
            color: #bb9a2a !important;
            text-decoration: underline;
            font-weight: bold;
        }
        .membership-cart-notice a:hover {
            color: #a8871a !important;
        }
        .membership-cart-notice .amount{
            display: inline !important;
        }
        .product-subtotal p{
            margin: 0 !important;
        }
        
    </style>
    <?php
    }
}

// Display original and discounted prices in cart and checkout
add_action('woocommerce_before_calculate_totals', 'apply_membership_pricing_to_cart', 10, 1);
// Change to use the price filter instead of the name filter
// add_filter('woocommerce_cart_item_name', 'display_membership_notice_in_cart', 10, 3);
add_filter('woocommerce_cart_item_subtotal', 'add_membership_notice_after_price', 20, 3);
add_filter('woocommerce_cart_item_subtotal', 'display_original_price_in_cart_subtotal', 10, 3);
add_action('wp_head', 'add_cart_price_css');
add_action('wp_head', 'add_cart_notice_css');