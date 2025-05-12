<?php
/*
	Template for layout= "div" or "2col" or "3col" or "4col"
	This is a custom version in the child theme that adds Upgrade/Downgrade buttons
*/

// Build the selectors for the levels div wrapper.
$wrapper_classes = array();
$wrapper_classes[] = 'pmpro_advanced_levels-div';
if ( ! empty( $layout ) ) {
	$wrapper_classes[] = 'pmpro_levels-' . esc_attr( $layout );
}
$wrapper_class = implode( ' ', array_unique( $wrapper_classes ) );
?>
<div id="pmpro_levels" class="<?php echo esc_attr( $wrapper_class ); ?>">
<?php
	foreach ( $pmpro_levels_filtered as $level ) {
		// Build the selectors for the single level elements.
		$element_classes = array();
		$element_classes[] = 'pmpro_level';
		if ( $highlight == $level->id ) {
			$element_classes[] = 'pmpro_level-highlight';
		}
		if ( $level->current_level ) {
			$element_classes[] = 'pmpro_level-current';
		}
		$element_class = implode( ' ', array_unique( $element_classes ) );
		?>
		<div id="pmpro_level-<?php echo esc_attr( $level->id ); ?>" class="<?php echo esc_attr( $element_class ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
				<?php do_action('pmproal_before_level', $level->id, $layout ); ?>
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php echo wp_kses( $level->name, pmproal_allowed_html() ); ?></h2>

				<?php if ( $layout === 'div' || $layout === '2col' || empty( $layout ) ) { ?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<?php if ( ! empty( $description ) && ! empty( $level->description ) ) { ?>
							<div class="pmpro_level-description">
								<?php echo wp_kses_post( wpautop($level->description) ); ?>
							</div> <!-- end .pmpro_level-description -->
						<?php } ?>
					</div> <!-- end .pmpro_card_content -->
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
						<div class="pmpro_level-meta">
							<?php
							// CUSTOM CODE - Modified button display logic with Upgrade/Downgrade text
							global $current_user;
							
							// Set up the button classes
							$button_classes = array();
							$button_classes[] = 'pmpro_btn';
							
							// If user is not logged in or has no membership level, show regular checkout button
							if ( !pmpro_hasMembershipLevel() || !$level->current_level ) {
								$button_classes[] = 'pmpro_btn-select';
								$button_link = add_query_arg( $level->link_arguments, pmpro_url( 'checkout', '', 'https' ) );
								$button_text = $checkout_button;
								
								// If user is logged in and has a different level, check for upgrade/downgrade
								if(is_user_logged_in() && pmpro_hasMembershipLevel() && !$level->current_level) {
									// Get user's current membership level
									$current_level = pmpro_getMembershipLevelForUser($current_user->ID);
									if(!empty($current_level)) {
										// Compare level prices to determine if it's an upgrade or downgrade
										$level_price = $level->initial_payment;
										$current_level_price = $current_level->initial_payment;
										
										if($level_price > $current_level_price) {
											// Higher price = upgrade
											$button_text = 'Upgrade';
										} else {
											// Lower price = downgrade
											$button_text = 'Downgrade';
										}
									}
								}
							} elseif($level->current_level) {
								// Get specific level details for the user
								$specific_level = pmpro_getSpecificMembershipLevelForUser($current_user->ID, $level->id);
								
								if(pmpro_isLevelExpiringSoon($specific_level)) {
									// Show renew button if the level is expiring soon
									$button_classes[] = 'pmpro_btn-select';
									$button_classes[] = 'pmpro_btn-renew';
									$button_link = add_query_arg($level->link_arguments, pmpro_url('checkout', '', 'https'));
									$button_text = $renew_button;
								} else {
									// Show account button otherwise
									$button_classes[] = 'disabled';
									$button_link = pmpro_url('account');
									$button_text = $account_button;
								}
							}
							
							// Output the button
							?>
							<a class="<?php echo esc_attr(implode(' ', array_unique($button_classes))); ?>" href="<?php echo esc_url($button_link); ?>"><?php echo esc_html($button_text); ?></a>
							<?php $show_price ? pmproal_getLevelPrice( $level, $price ) : ''; ?>
							<?php if ( ! empty ( $expiration ) ) {
								$level_expiration = pmpro_getLevelExpiration($level); ?>
								<p class="pmpro_level-expiration">
									<?php if ( empty ( $level_expiration ) ) {
										esc_html_e('Membership never expires.', 'pmpro-advanced-levels-shortcode');
									} else {
										echo wp_kses( $level_expiration, pmproal_allowed_html() );
									} ?>
								</p> <!-- end pmpro_level-expiration -->
							<?php } ?>
						</div> <!-- .pmpro_level-meta -->
					</div> <!-- end .pmpro_card_actions -->
					<?php
				} else {
					// This is a column-type div layout
					?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<?php $show_price ? pmproal_getLevelPrice( $level, $price ) : ''; ?>
						<p class="pmpro_level-select">
							<?php
							// CUSTOM CODE - Modified button display logic with Upgrade/Downgrade text
							global $current_user;
							
							// Set up the button classes
							$button_classes = array();
							$button_classes[] = 'pmpro_btn';
							
							// If user is not logged in or has no membership level, show regular checkout button
							if ( !pmpro_hasMembershipLevel() || !$level->current_level ) {
								$button_classes[] = 'pmpro_btn-select';
								$button_link = add_query_arg( $level->link_arguments, pmpro_url( 'checkout', '', 'https' ) );
								$button_text = $checkout_button;
								
								// If user is logged in and has a different level, check for upgrade/downgrade
								if(is_user_logged_in() && pmpro_hasMembershipLevel() && !$level->current_level) {
									// Get user's current membership level
									$current_level = pmpro_getMembershipLevelForUser($current_user->ID);
									if(!empty($current_level)) {
										// Compare level prices to determine if it's an upgrade or downgrade
										$level_price = $level->initial_payment;
										$current_level_price = $current_level->initial_payment;
										
										if($level_price > $current_level_price) {
											// Higher price = upgrade
											$button_text = 'Upgrade';
										} else {
											// Lower price = downgrade
											$button_text = 'Downgrade';
										}
									}
								}
							} elseif($level->current_level) {
								// Get specific level details for the user
								$specific_level = pmpro_getSpecificMembershipLevelForUser($current_user->ID, $level->id);
								
								if(pmpro_isLevelExpiringSoon($specific_level)) {
									// Show renew button if the level is expiring soon
									$button_classes[] = 'pmpro_btn-select';
									$button_classes[] = 'pmpro_btn-renew';
									$button_link = add_query_arg($level->link_arguments, pmpro_url('checkout', '', 'https'));
									$button_text = $renew_button;
								} else {
									// Show account button otherwise
									$button_classes[] = 'disabled';
									$button_link = pmpro_url('account');
									$button_text = $account_button;
								}
							}
							
							// Output the button
							?>
							<a class="<?php echo esc_attr(implode(' ', array_unique($button_classes))); ?>" href="<?php echo esc_url($button_link); ?>"><?php echo esc_html($button_text); ?></a>
						</p> <!-- end .pmpro_level-select -->
						<?php if ( ! empty( $description ) && ! empty( $level->description ) ) { ?>
							<div class="pmpro_level-description">
								<?php echo wp_kses_post( wpautop($level->description) ); ?>
							</div> <!-- end .pmpro_level-description -->
						<?php } ?>
					</div> <!-- end .pmpro_card_content -->
					<?php if ( ! empty ( $expiration ) ) { ?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_actions' ) ); ?>">
							<div class="pmpro_level-meta">
								<?php $level_expiration = pmpro_getLevelExpiration($level); ?>
								<p class="pmpro_level-expiration">
									<?php if ( empty ( $level_expiration ) ) {
										esc_html_e('Membership never expires.', 'pmpro-advanced-levels-shortcode');
									} else {
										echo wp_kses( $level_expiration, pmproal_allowed_html() );
									} ?>
								</p> <!-- end pmpro_level-expiration -->
							</div> <!-- .pmpro_level-meta -->
						</div> <!-- end .pmpro_card_actions -->
					<?php } ?>
				<?php } ?>
				<?php do_action('pmproal_after_level', $level->id, $layout); ?>
			</div> <!-- end .pmpro_card -->
		</div><!-- .pmpro_level -->
		<?php
	}
?>
</div> <!-- #pmpro_levels, .row --> 