<?php
// Prevent specific roles and guests from purchasing
function restrict_purchasing_by_role() {

    // Get current user
    $user = wp_get_current_user();
    
    // Define restricted roles (add/remove roles as needed)
    $restricted_roles = array('dr', 'pmpro_role_4','Doctor');
    
    // Check if user has any restricted role
    $user_roles = $user->roles;
    $has_restricted_role = !empty(array_intersect($restricted_roles, $user_roles));
    
    if ($has_restricted_role) {
        // Remove add to cart buttons
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
        remove_action('woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30);
        remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
        remove_action('woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30);
        
        // Add custom message
        add_action('woocommerce_single_product_summary', 'role_purchase_restriction_message', 30);
    }
}
add_action('init', 'restrict_purchasing_by_role');

// Custom message for guests
function guest_purchase_restriction_message() {
    echo '<div class="woocommerce-info" style="margin: 20px 0; padding: 15px; background: #f7f7f7; border-left: 4px solid #2ea2cc;">';
    echo '<strong>Please log in to purchase products.</strong> <a href="' . wp_login_url(get_permalink()) . '">Login here</a>';
    echo '</div>';
}

// Custom message for restricted roles
function role_purchase_restriction_message() {
    echo '<div class="woocommerce-info" style="margin: 20px 0; padding: 15px; background: #f7f7f7; border-left: 4px solid #dc3232;">';
    echo '<strong>Your account type does not have permission to purchase products.</strong> Please contact us for assistance.';
    echo '</div>';
}

// Prevent adding to cart via AJAX
function prevent_add_to_cart_for_restricted_users($passed, $product_id, $quantity) {
 
    // Check restricted roles
    $user = wp_get_current_user();
    $restricted_roles = array('dr', 'pmpro_role_4','Doctor');
    $user_roles = $user->roles;
    $has_restricted_role = !empty(array_intersect($restricted_roles, $user_roles));
    
    if ($has_restricted_role) {
        wc_add_notice('Your account type cannot purchase products.', 'error');
        return false;
    }
    
    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'prevent_add_to_cart_for_restricted_users', 10, 3);

// Prevent access to checkout page
function restrict_checkout_access() {
    // Only run on checkout page
    if (!is_checkout()) {
        return;
    }
    
    // Get current user
    $user = wp_get_current_user();
    
    // Define restricted roles
    $restricted_roles = array('dr', 'pmpro_role_4','Doctor');
    $user_roles = $user->roles;
    $has_restricted_role = !empty(array_intersect($restricted_roles, $user_roles));
    
    if ($has_restricted_role) {
        // Clear cart for restricted users
        WC()->cart->empty_cart();
        
        // Add error notice
        wc_add_notice('Your account type does not have permission to purchase products. Please contact us for assistance.', 'error');
        
        // Redirect to shop page or home page
        wp_redirect(wc_get_page_permalink('shop'));
        exit;
    }
}
add_action('template_redirect', 'restrict_checkout_access');

// Prevent checkout form submission
function prevent_checkout_process_for_restricted_users() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wc_add_notice('Please log in to complete your purchase.', 'error');
        return;
    }
    
    // Get current user
    $user = wp_get_current_user();
    
    // Define restricted roles
    $restricted_roles = array('dr', 'pmpro_role_4','Doctor');
    $user_roles = $user->roles;
    $has_restricted_role = !empty(array_intersect($restricted_roles, $user_roles));
    
    if ($has_restricted_role) {
        // Clear cart
        WC()->cart->empty_cart();
        
        // Add error notice
        wc_add_notice('Your account type cannot complete purchases. Order has been cancelled.', 'error');
        
        // Prevent checkout processing
        wp_die('Access denied. Your account type cannot complete purchases.');
    }
}
add_action('woocommerce_checkout_process', 'prevent_checkout_process_for_restricted_users');


?>