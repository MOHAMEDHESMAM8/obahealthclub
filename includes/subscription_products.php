<?php


// function remove_sfw_add_to_cart_validation() {
//     global $wp_filter;
    
//     // Remove all callbacks for this hook with priority 10
//     if (isset($wp_filter['woocommerce_add_to_cart_validation']) && 
//         isset($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10])) {
        
//         foreach ($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10] as $key => $callback) {
//             // Check if this is the callback we want to remove
//             if (is_array($callback['function']) && 
//                 is_object($callback['function'][0]) && 
//                 $callback['function'][1] === 'wps_sfw_woocommerce_add_to_cart_validation') {
                
//                 unset($wp_filter['woocommerce_add_to_cart_validation']->callbacks[10][$key]);
//                 return;
//             }
//         }
//     }
// }
// add_action('init', 'remove_sfw_add_to_cart_validation', 20); // Higher priority than plugin init


// add_filter( 'wps_sfw_show_quantity_fields_for_susbcriptions', 'show_quantity_for_subscription_products', 10, 2 );

// function show_quantity_for_subscription_products( $return, $product ) {
//     // Return false to show the quantity field
//     return false;
// }



?>