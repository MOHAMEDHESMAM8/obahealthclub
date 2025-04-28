<?php
/**
 * Theme functions and definitions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include membership label functionality
require_once get_stylesheet_directory() . '/membership-label.php';

// Include product shortcodes
require_once get_stylesheet_directory() . '/product-shortcodes.php';

// Include my-account-products-tab.php
require_once get_stylesheet_directory() . '/my-account-products-tab.php';

// Include pmpro-before-cancel.php
require_once get_stylesheet_directory() . '/pmpro-before-cancel.php';


