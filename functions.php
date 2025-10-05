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
        '1.6.17'
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

// Include display_name.php
require_once get_stylesheet_directory() . '/includes/display_name.php';









// add_action('woocommerce_review_order_before_payment', 'show_estimated_delivery_note');

// function show_estimated_delivery_note() {
//     $chosen_methods = WC()->session->get('chosen_shipping_methods');
//     $chosen_method = $chosen_methods[0] ?? '';

//     $estimate = get_delivery_estimate_note_based_on_method($chosen_method);

//     if ($estimate) {
//         echo '<div class="woocommerce-info"><strong>Estimated Delivery:</strong> ' . esc_html($estimate) . '</div>';
//     }
// }

// function get_delivery_estimate_note_based_on_method($method_id) {
//     $parts = explode('|', $method_id);
//     $service_key = strtolower($parts[1] ?? '');

//     $map = [
//         // USPS Domestic
//         'usps_first_class_mail'       => '2–4 business days',
//         'usps_ground_advantage'       => '3–6 business days',
//         'usps_parcel_select_ground'   => '3–6 business days',
//         'usps_media_mail'             => '2–8 business days',

//         // UPS Domestic
//         'ups_ground_saver'            => '2–7 business days',

//         // USPS Intl
//         'usps_first_class_mail_international' => '7–21 days',
//         'usps_priority_mail_international'    => '6–10 business days',

//         // GlobalPost
//         'globalpost_economy_international'    => '7–14 days',
//         'globalpost_standard_international'   => '6–10 days',

//         // UPS Intl
//         'ups_worldwide_expedited'             => '2–5 business days',
//     ];

//     foreach ($map as $key => $estimate) {
//         if (strpos($service_key, $key) !== false) {
//             return $estimate;
//         }
//     }

//     return $estimate; // fallback if unknown service
// }
// 
// 
// 
// 
// 





/**
 * Create Custom User Avatars with the Register Helper Add On
 *
 * Allow members to upload their avatar using a Register Helper field during checkout or on the Member Profile Edit page.
 *  
 * title: Create Custom User Avatars with the Register Helper Add On
 * layout: snippet
 * collection: add-ons, register-helper
 * category: user-avatars
 * 
 * You can add this recipe to your site by creating a custom plugin
 * or using the Code Snippets plugin available for free in the WordPress repository.
 * Read this companion article for step-by-step directions on either method.
 * https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */

// Filter the saved or updated User Avatar meta field value and add the image to the Media Library.
function my_updated_user_avatar_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
    // Change user_avatar to your Register Helper file upload name.
    if ( 'user_avatar' === $meta_key ) {
        $user_info     = get_userdata( $user_id );
        $filename      = $meta_value['fullpath'];
        $filetype      = wp_check_filetype( basename( $filename ), null );
        $wp_upload_dir = wp_upload_dir();
        $attachment    = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_status'    => 'inherit',
        );
        $attach_id     = wp_insert_attachment( $attachment, $filename );
        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );
        update_user_meta( $user_id, 'wp_user_avatar', $attach_id );
    }
}
add_action( 'added_user_meta', 'my_updated_user_avatar_user_meta', 10, 4 );
add_action( 'updated_user_meta', 'my_updated_user_avatar_user_meta', 10, 4 );

// Filter the display of the the get_avatar function to use our local avatar.
function my_user_avatar_filter( $avatar, $id_or_email, $size, $default, $alt ) {
    $my_user = get_userdata( $id_or_email );
    if ( ! empty( $my_user ) ) {
        $avatar_id = get_user_meta( $my_user->ID, 'wp_user_avatar', true );
        if ( ! empty( $avatar_id ) ) {
            $avatar = wp_get_attachment_image_src( $avatar_id, array( $size, $size) );
            $avatar = "<img alt='{$alt}' src='{$avatar[0]}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }
    }
    return $avatar;
}
add_filter( 'get_avatar', 'my_user_avatar_filter', 20, 5 );

// Add the User Avatar field at checkout and on the profile edit forms.
function my_pmprorh_init_user_avatar() {
    //don't break if Register Helper is not loaded
    if ( ! function_exists( 'pmprorh_add_registration_field' ) ) {
        return false;
    }
    //define the fields
    $fields   = array();
    $fields[] = new PMProRH_Field(
        'user_avatar',              // input name, will also be used as meta key
        'file',                 // type of field
        array(
            'label'     => 'Avatar',
            'hint'      => 'Recommended size is 100px X 100px',
            'profile'   => true,    // show in user profile
            'preview'   => true,    // show a preview-sized version of the image
            'addmember' => true,
            'allow_delete' => true,
        )
    );

    //add the fields into a new checkout_boxes are of the checkout page
    foreach ( $fields as $field ) {
        pmprorh_add_registration_field(
            'checkout_boxes', // location on checkout page
            $field            // PMProRH_Field object
        );
    }
}
add_action( 'init', 'my_pmprorh_init_user_avatar' );












add_action( 'woocommerce_cart_calculate_fees', 'add_lab_test_category_fee', 20, 1 );
function add_lab_test_category_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    $percentage = 0.06; // 6%
    $fee_amount = 0;

    // Loop through cart items
    foreach ( $cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        // Check if product is in "lab-test" category
        if ( has_term( 'lab-test', 'product_cat', $product_id ) ) {
            $product_price = $product->get_price(); // price per unit
            $quantity = $cart_item['quantity'];

            // Calculate fee for this product line
            $fee_amount += $product_price * $quantity * $percentage;
        }
    }

    if ( $fee_amount > 0 ) {
        // Add fee as a separate line, like shipping
        $cart->add_fee( 'Additional Cost (6%)', $fee_amount, true, '' );
    }
}














// Remove shipping rates only if ALL items are from 'lab-test' category
add_filter( 'woocommerce_package_rates', 'conditionally_remove_shipping_for_lab_test_only', 10, 2 );
function conditionally_remove_shipping_for_lab_test_only( $rates, $package ) {
    $has_non_lab = false;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( ! has_term( 'lab-test', 'product_cat', $cart_item['product_id'] ) ) {
            $has_non_lab = true;
            break;
        }
    }

    // If no non-lab products, remove all shipping rates
    if ( ! $has_non_lab ) {
        return [];
    }

    return $rates;
}

// Tell WooCommerce that shipping is not needed if only lab-test items are in the cart
add_filter( 'woocommerce_cart_needs_shipping', 'conditionally_hide_shipping_section_for_lab_only' );
function conditionally_hide_shipping_section_for_lab_only( $needs_shipping ) {
    $has_non_lab = false;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( ! has_term( 'lab-test', 'product_cat', $cart_item['product_id'] ) ) {
            $has_non_lab = true;
            break;
        }
    }

    // Hide shipping section only if all products are lab-test
    return $has_non_lab ? $needs_shipping : false;
}

add_filter( 'woocommerce_states', 'force_state_dropdown_with_dummy' );
function force_state_dropdown_with_dummy( $states ) {
    $states['US'] = array(
        'MI' => __( 'Michigan', 'woocommerce' ),
        'XX' => __( '', 'woocommerce' ), // Dummy hidden option
    );
    return $states;
}

add_filter( 'woocommerce_checkout_fields', 'set_default_michigan_dropdown' );
function set_default_michigan_dropdown( $fields ) {
    // Set default country and state
    $fields['billing']['billing_country']['default'] = 'US';
    $fields['billing']['billing_state']['default'] = 'MI';

    $fields['shipping']['shipping_country']['default'] = 'US';
    $fields['shipping']['shipping_state']['default'] = 'MI';

    return $fields;
}

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    $user = wp_get_current_user();

    if ( in_array( 'dr', (array) $user->roles, true ) )
    {
        return [];
    }
    return $items;
});