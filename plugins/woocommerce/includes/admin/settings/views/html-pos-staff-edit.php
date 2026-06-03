<?php
/**
 * Admin view: edit an existing POS staff member (preset + PIN).
 *
 * @package WooCommerce\Admin\Settings
 * @since   11.0.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::edit_output().
 *
 * @var bool     $has_pin                Whether the user already has a PIN.
 * @var int      $user_id                User ID.
 * @var WP_User  $user                   User object.
 * @var string   $current_pos_preset     Current POS preset meta value.
 * @var string[] $assignable_pos_presets List of assignable POS preset identifiers.
 */

if ( ! isset( $has_pin, $user_id, $user, $current_pos_preset, $assignable_pos_presets ) || ! $user instanceof WP_User ) {
	return;
}

// Post back to the same edit URL so a failed save (e.g. PIN collision) re-renders
// the form pre-filled instead of bouncing to the list view.
$form_action_url = add_query_arg(
	array(
		'page'       => 'wc-settings',
		'tab'        => 'point-of-sale',
		'section'    => 'staff',
		'edit-staff' => $user_id,
	),
	admin_url( 'admin.php' )
);

$cap_labels = array(
	POSCapabilities::CAP_PROCESS_SALES  => __( 'Process sales', 'woocommerce' ),
	POSCapabilities::CAP_VIEW_ORDERS    => __( 'View orders', 'woocommerce' ),
	POSCapabilities::CAP_APPLY_COUPONS  => __( 'Apply coupons', 'woocommerce' ),
	POSCapabilities::CAP_CREATE_COUPONS => __( 'Create coupons', 'woocommerce' ),
	POSCapabilities::CAP_ISSUE_REFUNDS  => __( 'Issue refunds', 'woocommerce' ),
	POSCapabilities::CAP_VIEW_SETTINGS  => __( 'View POS settings', 'woocommerce' ),
	POSCapabilities::CAP_EDIT_SETTINGS  => __( 'Edit POS settings', 'woocommerce' ),
	POSCapabilities::CAP_MANAGE_STAFF   => __( 'Manage POS staff', 'woocommerce' ),
	POSCapabilities::CAP_EXIT_POS       => __( 'Exit POS', 'woocommerce' ),
);
?>

<div id="pos-staff-fields" class="settings-panel">
	<h2><?php esc_html_e( 'Edit staff', 'woocommerce' ); ?></h2>

	<form method="post" action="<?php echo esc_url( $form_action_url ); ?>">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'User', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<strong><?php echo esc_html( $user->display_name ); ?></strong>
						<span class="description">(<?php echo esc_html( $user->user_email ); ?>)</span>
					</td>
				</tr>
				<tr>
					<th scope="row" class="titledesc">
						<label for="pos_preset"><?php esc_html_e( 'POS role', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<select id="pos_preset" name="pos_preset" required>
							<?php foreach ( $assignable_pos_presets as $preset_value ) : ?>
								<option value="<?php echo esc_attr( $preset_value ); ?>"<?php selected( $preset_value, $current_pos_preset ); ?>>
									<?php echo esc_html( POSCapabilities::preset_label( $preset_value ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>

						<div class="wc-pos-staff-cap-preview">
							<p class="description"><?php esc_html_e( 'Permissions granted by this role:', 'woocommerce' ); ?></p>
							<?php foreach ( $assignable_pos_presets as $preset_value ) : ?>
								<ul
									class="wc-pos-staff-cap-list"
									data-preset="<?php echo esc_attr( $preset_value ); ?>"
									<?php echo $preset_value === $current_pos_preset ? '' : 'hidden'; ?>>
									<?php
									$caps = POSCapabilities::capabilities_for_preset( $preset_value );
									foreach ( $cap_labels as $cap_key => $cap_label ) :
										if ( empty( $caps[ $cap_key ] ) ) {
											continue;
										}
										?>
										<li><?php echo esc_html( $cap_label ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row" class="titledesc">
						<label for="pos_pin_<?php echo esc_attr( (string) $user_id ); ?>">
							<?php
							echo esc_html(
								$has_pin
									? __( 'Reset PIN', 'woocommerce' )
									: __( 'Set PIN', 'woocommerce' )
							);
							?>
						</label>
					</th>
					<td class="forminp">
						<input
							type="password"
							id="pos_pin_<?php echo esc_attr( (string) $user_id ); ?>"
							name="pos_pin"
							pattern="[0-9]*"
							inputmode="numeric"
							minlength="4"
							maxlength="4"
							class="input-text"
							autocomplete="off"
						/>
						<p class="description">
							<?php
							echo esc_html(
								$has_pin
									? __( 'Leave blank to keep the existing PIN. Enter exactly 4 digits to replace it.', 'woocommerce' )
									: __( 'Enter exactly 4 digits.', 'woocommerce' )
							);
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php wp_nonce_field( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user_id ); ?>" />
		<p class="submit">
			<?php submit_button( __( 'Save', 'woocommerce' ), 'primary', 'save_pos_staff', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>

<script>
( function ( $ ) {
	$( function () {
		var $preset     = $( '#pos_preset' );
		var $capPreview = $( '.wc-pos-staff-cap-preview' );

		$preset.on( 'change', function () {
			var selected = $preset.val();
			$capPreview.find( '.wc-pos-staff-cap-list' ).attr( 'hidden', true );
			if ( selected ) {
				$capPreview.find( '[data-preset="' + selected + '"]' ).removeAttr( 'hidden' );
			}
		} );
	} );
}( jQuery ) );
</script>
