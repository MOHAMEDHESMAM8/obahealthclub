<?php
// ... existing code ...

/**
 * Filter the PMPro Advanced Levels shortcode checkout button text
 * to show "Upgrade" or "Downgrade" based on level comparison
 */
function my_custom_pmpro_checkout_button_text($button_text, $level_id) {
    // Only modify if user is logged in
    if (!is_user_logged_in()) {
        return $button_text;
    }
    
    global $current_user;
    
    // Get user's current membership level
    $current_level = pmpro_getMembershipLevelForUser($current_user->ID);
    
    // If user has no level, return original text
    if (empty($current_level)) {
        return $button_text;
    }
    
    // Don't modify button text for user's current level
    if ($level_id == $current_level->ID) {
        return $button_text;
    }
    
    // Get level info for the level being displayed
    $level = pmpro_getLevel($level_id);
    
    if (!empty($level) && !empty($current_level)) {
        // Compare level prices to determine if it's an upgrade or downgrade
        // You can modify this logic based on your specific pricing structure or level hierarchy
        $level_price = $level->initial_payment;
        $current_level_price = $current_level->initial_payment;
        
        if ($level_price > $current_level_price) {
            // Higher price = upgrade
            return __('Upgrade', 'pmpro-advanced-levels-shortcode');
        } else {
            // Lower price = downgrade
            return __('Downgrade', 'pmpro-advanced-levels-shortcode');
        }
    }
    
    return $button_text;
}

/**
 * Filter the checkout button text in the Advanced Levels shortcode
 */
function modify_pmpro_advanced_levels_buttons() {
    add_filter('pmpro_advanced_levels_checkout_button', 'my_custom_pmpro_checkout_button_text', 10, 2);
}
add_action('wp', 'modify_pmpro_advanced_levels_buttons');

/**
 * Custom implementation of pmproal_level_button to show "Upgrade" or "Downgrade"
 * based on the user's current level compared to the level being displayed.
 *
 * This function will replace the original plugin function.
 */
if (!function_exists('pmproal_level_button')) {
    function pmproal_level_button($level, $checkout_button, $renew_button, $account_button) {
        global $current_user;

        // Set up the button classes
        $button_classes = array();
        $button_classes[] = 'pmpro_btn';

        // If user is not logged in or has no membership level, show regular checkout button
        if (!pmpro_hasMembershipLevel() || !$level->current_level) {
            $button_classes[] = 'pmpro_btn-select';
            $button_link = add_query_arg($level->link_arguments, pmpro_url('checkout', '', 'https'));
            $button_text = $checkout_button;
        } elseif ($level->current_level) {
            // Get user's current membership level
            $current_level = pmpro_getMembershipLevelForUser($current_user->ID);
            
            // Get specific level details for the user
            $specific_level = pmpro_getSpecificMembershipLevelForUser($current_user->ID, $level->id);
            
            if (pmpro_isLevelExpiringSoon($specific_level)) {
                // Show renew button if the level is expiring soon
                $button_classes[] = 'pmpro_btn-select';
                $button_classes[] = 'pmpro_btn-renew';
                $button_link = add_query_arg($level->link_arguments, pmpro_url('checkout', '', 'https'));
                $button_text = $renew_button;
            } elseif ($level->id == $current_level->ID) {
                // User already has this level - show account button
                $button_classes[] = 'disabled';
                $button_link = pmpro_url('account');
                $button_text = $account_button;
            } else {
                // Compare level prices to determine if it's an upgrade or downgrade
                // You can modify this logic based on your specific pricing structure or level hierarchy
                $level_price = $level->initial_payment;
                $current_level_price = $current_level->initial_payment;
                
                $button_classes[] = 'pmpro_btn-select';
                $button_link = add_query_arg($level->link_arguments, pmpro_url('checkout', '', 'https'));
                
                if ($level_price > $current_level_price) {
                    // Higher price = upgrade
                    $button_text = 'Upgrade';
                } else {
                    // Lower price = downgrade
                    $button_text = 'Downgrade';
                }
            }
        }

        // Output the button
        ?>
        <a class="<?php echo esc_attr(implode(' ', array_unique($button_classes))); ?>" href="<?php echo esc_url($button_link); ?>"><?php echo esc_html($button_text); ?></a>
        <?php
    }
}

/**
 * Modify PMPro Advanced Levels Shortcode buttons to show "Upgrade" or "Downgrade"
 * based on the user's current level
 */
function modify_pmproal_buttons() {
    // Only proceed if the user is logged in
    if (!is_user_logged_in()) {
        return;
    }
    
    // Start output buffering to capture and modify the button HTML
    add_action('pmproal_level_button', function($level, $checkout_button, $renew_button, $account_button) {
        // If the user is on their current level, or it's an expiring level, don't modify
        if ($level->current_level) {
            return;
        }
        
        global $current_user;
        
        // Get user's current level
        $current_level = pmpro_getMembershipLevelForUser($current_user->ID);
        
        // If user has no level, don't modify
        if (empty($current_level)) {
            return;
        }
        
        // Get the level price information
        $level_price = $level->initial_payment;
        $current_level_price = $current_level->initial_payment;
        
        // Start output buffering
        ob_start();
    }, 9, 4); // Priority 9 to run before the original function
    
    // End output buffering and modify content
    add_action('pmproal_level_button', function($level, $checkout_button, $renew_button, $account_button) {
        // If the user is on their current level, or it's an expiring level, don't modify
        if ($level->current_level) {
            return;
        }
        
        global $current_user;
        
        // Get user's current level
        $current_level = pmpro_getMembershipLevelForUser($current_user->ID);
        
        // If user has no level, don't modify
        if (empty($current_level)) {
            return;
        }
        
        // Get the level price information
        $level_price = $level->initial_payment;
        $current_level_price = $current_level->initial_payment;
        
        // Get the buffered content
        $button_html = ob_get_clean();
        
        // Modify the text based on level comparison
        if ($level_price > $current_level_price) {
            $button_html = str_replace('>' . $checkout_button . '<', '>Upgrade<', $button_html);
        } else {
            $button_html = str_replace('>' . $checkout_button . '<', '>Downgrade<', $button_html);
        }
        
        // Output the modified button HTML
        echo $button_html;
    }, 11, 4); // Priority 11 to run after the original function
}
add_action('init', 'modify_pmproal_buttons');

/**
 * Tell WordPress to look for PMPro Advanced Levels templates in our child theme first
 */
function my_pmpro_advanced_levels_template_loader($template, $template_name, $template_path) {
    // Only modify PMPro Advanced Levels Shortcode templates
    if ($template_path !== 'pmpro-advanced-levels-shortcode/') {
        return $template;
    }
    
    // Check if template exists in child theme
    $child_theme_template = get_stylesheet_directory() . '/pmpro-advanced-levels-shortcode/templates/' . $template_name;
    
    // If the template exists in our child theme directory, use it
    if (file_exists($child_theme_template)) {
        return $child_theme_template;
    }
    
    // Otherwise, return the original template
    return $template;
}
add_filter('pmpro_locate_template', 'my_pmpro_advanced_levels_template_loader', 10, 3); 