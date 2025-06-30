<?php


function custom_display_username_shortcode() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        $first_name = $current_user->user_firstname;
        $last_name  = $current_user->user_lastname;

        // Fallback in case names are empty
        if ( empty($first_name) && empty($last_name) ) {
            return esc_html( $current_user->display_name );
        }

        return esc_html( trim("$first_name $last_name") );
    } else {
        return 'Guest';
    }
}
add_shortcode('username', 'custom_display_username_shortcode');