<?php
/**
 * Admin view: edit an existing POS staff member (role + PIN).
 *
 * @package WooCommerce\Admin\Settings
 * @since   10.9.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::edit_output().
 *
 * @var bool     $has_pin              Whether the user already has a PIN.
 * @var int      $user_id              User ID.
 * @var WP_User  $user                 User object.
 * @var string   $current_pos_role     Current POS role meta value.
 * @var string[] $assignable_pos_roles List of assignable POS role identifiers.
 */

if ( ! isset( $has_pin, $user_id, $user, $current_pos_role, $assignable_pos_roles ) || ! $user instanceof WP_User ) {
	return;
}
?>

<div id="pos-staff-fields" class="settings-panel">
	<h2><?php esc_html_e( 'Edit staff', 'woocommerce' ); ?></h2>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>">
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
						<label for="pos_role"><?php esc_html_e( 'POS role', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<select id="pos_role" name="pos_role" required>
							<?php foreach ( $assignable_pos_roles as $role_value ) : ?>
								<option value="<?php echo esc_attr( $role_value ); ?>"<?php selected( $role_value, $current_pos_role ); ?>>
									<?php echo esc_html( POSCapabilities::role_label( $role_value ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
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
