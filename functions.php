<?php
/**
 * Theme functions and definitions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue parent and child theme styles
function thegem_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css', array());
    wp_enqueue_style('child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        '1.5'
    );
}
add_action('wp_enqueue_scripts', 'thegem_child_enqueue_styles');

// Include membership label functionality
require_once get_stylesheet_directory() . '/includes/membership-label.php';

// Include product shortcodes
require_once get_stylesheet_directory() . '/includes/product-shortcodes.php';

// Include my-account-products-tab.php
require_once get_stylesheet_directory() . '/includes/my-account-products-tab.php';

// Include pmpro-before-cancel.php
require_once get_stylesheet_directory() . '/includes/pmpro-before-cancel.php';

// Include pmpro-advanced-levels.php
require_once get_stylesheet_directory() . '/includes/pmpro-advanced-levels.php';

/**
 * Determines the appropriate button text for membership levels based on user's current level
 * 
 * @param object $level The level object to display
 * @param string $checkout_button The default checkout button text
 * @param string $renew_button The text for renewing the current level
 * @param string $account_button The text for the user's current level
 * @return void Outputs the button HTML
 */
function pmproal_level_button_custom($level, $checkout_button, $renew_button, $account_button) {
    global $current_user, $pmpro_pages;
    
    // Get the user's current level ID if they have one
    $current_level_id = 0;
    if (pmpro_hasMembershipLevel()) {
        $current_level = pmpro_getMembershipLevelForUser($current_user->ID);
        if (!empty($current_level)) {
            $current_level_id = $current_level->id;
        }
    }

    $button_text = $checkout_button;
    $button_class = 'pmpro_btn';

    // If this is user's current level
    if ($level->current_level) {
        $button_text = $account_button;
        $button_class = 'pmpro_btn pmpro_btn-disabled pmpro_btn-current';
        $button_url = pmpro_url('account');
    } 
    // If user has a membership level, but it's not this one
    else if ($current_level_id > 0) {
        // Determine if this is an upgrade or downgrade based on level ID
        if ($level->id > $current_level_id) {
            $button_text = __('Upgrade', 'pmpro-advanced-levels-shortcode');
            $button_class = 'pmpro_btn pmpro_btn-upgrade';
        } else {
            $button_text = __('Downgrade', 'pmpro-advanced-levels-shortcode');
            $button_class = 'pmpro_btn pmpro_btn-downgrade';
        }
        $button_url = pmpro_url('checkout', '?level=' . $level->id);
        
        // Add discount code if available
        if (!empty($level->link_arguments['discount_code'])) {
            $button_url .= '&discount_code=' . $level->link_arguments['discount_code'];
        }
    } 
    // User doesn't have a membership level
    else {
        $button_url = pmpro_url('checkout', '?level=' . $level->id);
        
        // Add discount code if available
        if (!empty($level->link_arguments['discount_code'])) {
            $button_url .= '&discount_code=' . $level->link_arguments['discount_code'];
        }
    }
    
    // Output the button
    echo '<a href="' . esc_url($button_url) . '" class="' . esc_attr($button_class) . '">' . esc_html($button_text) . '</a>';
}

function child_theme_enqueue_usa_phone_field_script() {
    // 1. Load intlTelInput CSS
    
    // 4. Load your custom script (AFTER all dependencies)
    wp_enqueue_script(
        'usa-phone-field',
        get_stylesheet_directory_uri() . '/js/usa-phone-field.js',
        array(), // Requires jQuery & intlTelInput
        '2.9.4',
        true
    );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_usa_phone_field_script',20);


function enqueue_otp_verification_script() {
    // Only enqueue the script if the MinioRange OTP plugin is active
    wp_enqueue_script(
        'otp-verification-script',
        get_stylesheet_directory_uri() . '/js/verify-otp-script.js',
        array('jquery'),  // Add jQuery as a dependency
        '1.0.1',
        true  // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'enqueue_otp_verification_script');


function remove_sfw_add_to_cart_validation() {
    global $wp_filter;
    
    // Remove all callbacks for this hook with priority 10
    if (isset($wp_filter['woocommerce_add_to_cart_validation']) && 
        isset($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10])) {
        
        foreach ($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10] as $key => $callback) {
            // Check if this is the callback we want to remove
            if (is_array($callback['function']) && 
                is_object($callback['function'][0]) && 
                $callback['function'][1] === 'wps_sfw_woocommerce_add_to_cart_validation') {
                
                unset($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10][$key]);
                return;
            }
        }
    }
}
add_action('init', 'remove_sfw_add_to_cart_validation', 20); // Higher priority than plugin init


add_filter( 'wps_sfw_show_quantity_fields_for_susbcriptions', 'show_quantity_for_subscription_products', 10, 2 );

function show_quantity_for_subscription_products( $return, $product ) {
    // Return false to show the quantity field
    return false;
}