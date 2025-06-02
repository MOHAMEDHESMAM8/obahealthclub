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
function add_product_grid_to_dashboard()
{
    if (!pmpro_hasMembershipLevel('4')) {
        echo '<div class="dashboard-recommended-products">';
        echo '<h3>' . __('Recommended Products Just For You', 'woocommerce') . '</h3>';
        echo '<p>' . __('Based on your preferences and purchase history, we think you might like these products:', 'woocommerce') . '</p>';

        // Display the product grid shortcode
        echo do_shortcode('[product_grid count="12" grid="3"]');
        echo '</div>';
    }
}
add_action('woocommerce_account_dashboard', 'add_product_grid_to_dashboard', 20);



/**
 * Add custom CSS for the recommended products tab
 */
function recommended_products_tab_css()
{
    if (is_account_page()) {
?>
        <style>
            /* Style the tab content container */
            .dashboard-recommended-products h3 {

                margin-bottom: 10px !important;
            }

            .wcmtx-my-account-links.wcmtx-grid {
                display: none !important;
            }

            .wcmamtx_notice_div.dashboard_text {
                display: none !important;
            }

            .woocommerce-MyAccount-content-wrapper {
                background-color: #0A2148 !important;
                border: none !important;
            }

            .woocommerce-MyAccount-content-wrapper p strong,
            .woocommerce-MyAccount-content-wrapper * {
                color: #fff !important;
            }

            .woocommerce-MyAccount-content-wrapper input,
            .woocommerce-MyAccount-content-wrapper select,
            .woocommerce-MyAccount-content-wrapper textarea {
                color: var(--forms-fields-normal-color, #69727d) !important;
            }

            .woocommerce-MyAccount-content-wrapper a {
                color: #BB9A2A !important;
            }
        </style>
<?php
    }
}
add_action('wp_head', 'recommended_products_tab_css');

#-- Remove Become a Vendor Button --#
function remove_become_a_vendor_button()
{
    remove_action('woocommerce_after_my_account', [dokan()->frontend_manager->become_a_vendor, 'render_become_a_vendor_section']);
}
add_action('wp_head', 'remove_become_a_vendor_button');

function restrict_level_4_account_pages() {
    // Check if user has level 4 and is on restricted pages
    if (pmpro_hasMembershipLevel('4')) {
        $current_uri = $_SERVER['REQUEST_URI'];
        
        $restricted_pages = array(
            '/my-account/edit-account/',
            '/my-account/edit-address/', 
            '/my-account/orders/',
        );
        
        foreach ($restricted_pages as $page) {
            if (strpos($current_uri, $page) !== false) {
                // Redirect to doctor dashboard
                wp_redirect(home_url('/my-account/doctor-dashboard/'));
                exit;
            }
        }
        
        // Also block the main my-account page but allow doctor-dashboard
        if (preg_match('/\/my-account\/?$/', $current_uri)) {
            wp_redirect(home_url('/my-account/doctor-dashboard/'));
            exit;
        }
    }
}
add_action('template_redirect', 'restrict_level_4_account_pages');