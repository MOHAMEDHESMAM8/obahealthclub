<?php
/**
 * Custom Products Tab for WooCommerce My Account
 * 
 * Description: Adds a "Recommended Products" tab to the My Account page
 * and displays the product grid shortcode in that tab.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add the product grid to the dashboard content
 */
function add_product_grid_to_dashboard() {
    echo '<div class="dashboard-recommended-products">';
    echo '<h3>' . __('Recommended Products Just For You', 'woocommerce') . '</h3>';
    echo '<p>' . __('Based on your preferences and purchase history, we think you might like these products:', 'woocommerce') . '</p>';
    
    // Display the product grid shortcode
    echo do_shortcode('[product_grid count="12" grid="3"]');
    echo '</div>';
}
add_action('woocommerce_account_dashboard', 'add_product_grid_to_dashboard', 20);



/**
 * Add custom CSS for the recommended products tab
 */
function recommended_products_tab_css() {
    if (is_account_page()) {
        ?>
        <style>
            /* Style the tab content container */
            .dashboard-recommended-products h3{
 
                margin-bottom: 10px !important;
            }   
            .wcmtx-my-account-links.wcmtx-grid{
                display: none !important;
            }
            .wcmamtx_notice_div.dashboard_text{
                display: none !important;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'recommended_products_tab_css'); 