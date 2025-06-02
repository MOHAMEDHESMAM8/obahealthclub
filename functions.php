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

function child_theme_enqueue_usa_phone_field_script() {
    // 1. Load intlTelInput CSS
    
    // 4. Load your custom script (AFTER all dependencies)
    wp_enqueue_script(
        'usa-phone-field',
        get_stylesheet_directory_uri() . '/js/usa-phone-field.js',
        array(), // Requires jQuery & intlTelInput
        '3',
        true
    );
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_usa_phone_field_script',20);




// Include membership label functionality
require_once get_stylesheet_directory() . '/includes/membership-label.php';

// Include product shortcodes
require_once get_stylesheet_directory() . '/includes/product-shortcodes.php';

// Include my-account-edits.php
require_once get_stylesheet_directory() . '/includes/my-account-edits.php';

// Include pmpro-before-cancel.php
require_once get_stylesheet_directory() . '/includes/pmpro-before-cancel.php';

// Include subscription_products.php
require_once get_stylesheet_directory() . '/includes/subscription_products.php';

// Include signature-requirement.php
require_once get_stylesheet_directory() . '/includes/signature-requirement.php';

// Include restricted_roles.php
require_once get_stylesheet_directory() . '/includes/restricted_roles.php';





