<?php
/*
	Template for layout = "compare_table"
	This is a custom version in the child theme that adds Upgrade/Downgrade buttons
*/
?>
<div id="pmpro_levels_compare_table" class="pmpro_advanced_levels-compare_table">
	<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table', 'pmpro_compare_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table_header', 'pmpro_compare_table_header' ) ); ?>">&nbsp;</th>
				<?php
					foreach ( $pmpro_levels_filtered as $level ) {
						$column_classes = array();
						$column_classes[] = 'pmpro_level-' . $level->id;
						if ( $highlight == $level->id ) {
							$column_classes[] = 'pmpro_level-highlight';
						}
						if ( $level->current_level ) {
							$column_classes[] = 'pmpro_level-current';
						}
						$column_class = implode( ' ', array_unique( $column_classes ) );

						?>
						<th class="<?php echo esc_attr( $column_class ); ?>">
							<h2><?php echo wp_kses( $level->name, pmproal_allowed_html() ); ?></h2>
							<?php if ( ! empty( $description ) && ! empty( $level->description ) ) { ?>
								<div class="pmpro_level-description">
									<?php echo wp_kses_post( wpautop($level->description) ); ?>
								</div>
							<?php } ?>
							<?php if ( $show_price ) {
								pmproal_getLevelPrice( $level, $price );
							} ?>
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
							
							<?php if ( ! empty ( $expiration ) ) { ?>
								<div class="pmpro_level-expiration">
									<?php
										$level_expiration = pmpro_getLevelExpiration( $level );
										if ( empty( $level_expiration ) ) {
											esc_html_e( 'Membership never expires.', 'pmpro-advanced-levels-shortcode' );
										} else {
											echo wp_kses( $level_expiration, pmproal_allowed_html() );
										}
									?>
								</div> <!-- end pmpro_level-expiration -->
							<?php } ?>
						</th>
						<?php
					}
				?>
			</tr>
		</thead>
		<tbody>
			<?php
				// Check to see if the $compareitems are set
				if ( ! empty( $compare ) ) {
					foreach( $compareitems as $compareitem ) {
						// Break up the string
						$compareitem_values = explode( ',', $compareitem );
						$compareitem_header = false;
						// If a header exists (i.e. at least 2 items found).
						if ( count( $compareitem_values ) > 1 ) {
							$compareitem_header = htmlspecialchars($compareitem_values[0]);
						}
						?>
						<tr>
							<?php
								if ( ! empty( $compareitem_header ) ) {
									?>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table_header', 'pmpro_compare_table_header' ) ); ?>"><?php echo $compareitem_header; ?></th>
									<?php
								} else {
									?>
									<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table_header', 'pmpro_compare_table_header' ) ); ?>">&nbsp;</th>
									<?php
								}

								$value_count = 0;
								foreach ( $pmpro_levels_filtered as $level ) {
									$value_count++;
									$column_classes = array();
									$column_classes[] = 'pmpro_level-' . $level->id;
									if ( $highlight == $level->id ) {
										$column_classes[] = 'pmpro_level-highlight';
									}
									if ( $level->current_level ) {
										$column_classes[] = 'pmpro_level-current';
									}
									$column_class = implode( ' ', array_unique( $column_classes ) );
									?>
									<td class="<?php echo esc_attr( $column_class ); ?>">
										<?php
											// If we're on the first item (header) and we have a second value.
											if ( ! empty( $compareitem_header ) && isset( $compareitem_values[$value_count] ) ) {
												echo wp_kses( $compareitem_values[$value_count], pmproal_allowed_html() );
											} elseif( empty( $compareitem_header ) ) {
												// No header, so we just show the value.
												echo wp_kses( $compareitem_values[0], pmproal_allowed_html() );
											} else {
												echo '&nbsp;';
											}
										?>
									</td>
									<?php
								}
							?>
						</tr>
						<?php
					}
				}
			?>
			
			<?php if ( ! empty( $show_price ) && ! empty( $morelink ) ) { ?>
			<tr>
				<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table_header', 'pmpro_compare_table_header' ) ); ?>">&nbsp;</th>
				<?php
					foreach ( $pmpro_levels_filtered as $level ) {
						$column_classes = array();
						$column_classes[] = 'pmpro_level-' . $level->id;
						if ( $highlight == $level->id ) {
							$column_classes[] = 'pmpro_level-highlight';
						}
						if ( $level->current_level ) {
							$column_classes[] = 'pmpro_level-current';
						}
						$column_class = implode( ' ', array_unique( $column_classes ) );
						
						?>
						<td class="<?php echo esc_attr( $column_class ); ?>">
							<?php
							if ( ! empty( $more_button ) ) {
								$level_landing_page = pmproal_getLevelLandingPage( $level );
								if ( ! empty( $level_landing_page ) ) { ?>
									<div class="pmpro_level-select">
										<a href="<?php echo esc_url( get_permalink( $level_landing_page->ID ) ); ?>"><?php echo esc_html( $more_button ); ?></a>
									</div>
								<?php }
							}
							?>
						</td>
						<?php
					}
				?>
			</tr>
			<?php } ?>

			<tr class="pmpro_compare_table_select_row">
				<th class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_compare_table_header', 'pmpro_compare_table_header' ) ); ?>">&nbsp;</th>
				<?php
					foreach ( $pmpro_levels_filtered as $level ) {
						$column_classes = array();
						$column_classes[] = 'pmpro_level-' . $level->id;
						if ( $highlight == $level->id ) {
							$column_classes[] = 'pmpro_level-highlight';
						}
						if ( $level->current_level ) {
							$column_classes[] = 'pmpro_level-current';
						}
						$column_class = implode( ' ', array_unique( $column_classes ) );

						?>
						<td class="<?php echo esc_attr( $column_class ); ?>">
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
						</td>
						<?php
					}
				?>
			</tr>
		</tbody>
	</table>
</div> <!-- end pmpro_levels_compare_table --> 