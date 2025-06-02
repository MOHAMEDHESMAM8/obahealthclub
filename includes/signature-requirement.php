<?php
/**
 * Signature Requirement Functionality
 * Adds a checkbox to require signature upon delivery
 * and adds a note to the order when checked
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Process the checkbox value and append to customer provided note
 */
function process_signature_checkbox() {
    if (isset($_POST['signature']) && $_POST['signature'] == 1) {
        // Add the signature requirement to the customer provided notes
        if (isset($_POST['order_comments']) && !empty($_POST['order_comments'])) {
            $_POST['order_comments'] .= "\n\nIMPORTANT: Customer requires a signature when order is delivered";
        } else {
            $_POST['order_comments'] = "IMPORTANT: Customer requires a signature when order is delivered";
        }
        
        // Store in session so we can use it when order is processed
        WC()->session->set('signature', true);
    }
}
add_action('woocommerce_checkout_process', 'process_signature_checkbox');

/**
 * Add order note when signature is required
 */
function add_signature_order_note($order_id) {
    if (WC()->session->get('signature')) {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('IMPORTANT: Customer requires a signature when order is delivered', 'thegem-child'));
        
        // Add order meta to track this requirement
        update_post_meta($order_id, '_signature_required', 'yes');
        
        // Clear session data
        WC()->session->__unset('signature_required');
    }
}
add_action('woocommerce_checkout_order_processed', 'add_signature_order_note', 10, 1);

/**
 * Display signature requirement on admin order page
 */
function display_admin_order_signature_requirement($order) {
    $order_id = $order->get_id();
    $signature_required = get_post_meta($order_id, '_signature_required', true);
    
    if ($signature_required === 'yes') {
        echo '<p class="form-field form-field-wide">';
        echo '<mark class="order-status"><strong>' . __('Signature Required', 'thegem-child') . '</strong></mark>';
        echo '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'display_admin_order_signature_requirement', 10, 1); 