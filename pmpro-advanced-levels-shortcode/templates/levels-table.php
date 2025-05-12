<?php
/*
	Template for layout = "table"
	This is a custom version in the child theme that adds Upgrade/Downgrade buttons
*/
?>
<div id="pmpro_levels_table" class="pmpro_advanced_levels-table">
	<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_checkout_table', 'pmpro_checkout_table' ) ); ?>" width="100%" cellpadding="0" cellspacing="0" border="0">
		<thead>
			<tr>
				<th><?php esc_html_e('Level', 'pmpro-advanced-levels-shortcode');?></th>
				<?php if($show_price) { ?>
					<th><?php esc_html_e('Price', 'pmpro-advanced-levels-shortcode');?></th>
				<?php } ?>
				<?php if (!empty($expiration)) { ?>
					<th><?php esc_html_e('Expiration', 'pmpro-advanced-levels-shortcode');?></th>
				<?php } ?>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach($pmpro_levels_filtered as $level) {
				$row_classes = array();
				$row_classes[] = 'pmpro_level';
				if($highlight == $level->id) {
					$row_classes[] = 'pmpro_level-highlight';
				}
				if($level->current_level) {
					$row_classes[] = 'pmpro_level-current';
				}
				$row_class = implode(' ', array_unique($row_classes));
			?>
			<tr id="pmpro_level-<?php echo esc_attr( $level->id ); ?>" class="<?php echo esc_attr( $row_class ); ?>">
				<td>
					<h2><?php echo wp_kses( $level->name, pmproal_allowed_html() ); ?></h2>
					<?php if(!empty($description) && !empty($level->description)) { ?>
						<div class="pmpro_level-description">
							<?php echo wp_kses_post( wpautop($level->description) ); ?>
						</div>
					<?php } ?>
				</td>
				<?php if($show_price) { ?>
				<td>
					<?php pmproal_getLevelPrice( $level, $price ); ?>
				</td>
				<?php } ?>
				<?php if(!empty($expiration)) { ?>
				<td>
					<?php 
						$level_expiration = pmpro_getLevelExpiration($level);
						if(empty($level_expiration)) {
							esc_html_e('Membership never expires.', 'pmpro-advanced-levels-shortcode');
						} else {
							echo wp_kses( $level_expiration, pmproal_allowed_html() );
						}
					?>
				</td>
				<?php } ?>
				<td>
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
			</tr>
			<?php
			}
			?>
		</tbody>
	</table>
</div> <!-- end pmpro_levels_table --> 