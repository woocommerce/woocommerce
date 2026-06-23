<?php
/**
 * Admin view: pick an existing WP user to grant POS access to.
 *
 * Fronts the regular edit screen — the admin chooses a user here, then submits
 * to be redirected to ?edit-staff=<id> where preset + PIN get set.
 *
 * @package WooCommerce\Admin\Settings
 * @since   11.0.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::pick_user_output().
 *
 * @var string $assigned_user_ids_csv CSV of user IDs already assigned a POS preset.
 * @var string $form_action_url       Action URL to post the picker form back to.
 */

if ( ! isset( $assigned_user_ids_csv, $form_action_url ) ) {
	return;
}
?>

<div id="pos-staff-fields" class="settings-panel">
	<h2><?php esc_html_e( 'Grant POS access to existing user', 'woocommerce' ); ?></h2>

	<form method="post" action="<?php echo esc_url( $form_action_url ); ?>">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row" class="titledesc">
						<label for="user_id"><?php esc_html_e( 'User', 'woocommerce' ); ?> <span class="description">*</span></label>
					</th>
					<td class="forminp">
						<select
							class="wc-customer-search"
							id="user_id"
							name="user_id"
							style="width: 50%;"
							data-placeholder="<?php esc_attr_e( 'Search by name, email, or login&hellip;', 'woocommerce' ); ?>"
							data-allow_clear="true"
							data-exclude="<?php echo esc_attr( $assigned_user_ids_csv ); ?>"
							required>
						</select>
						<p class="description">
							<?php esc_html_e( 'Continue to the next step to assign a POS role and set a PIN.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php wp_nonce_field( 'woocommerce-pos-staff-pick', 'woocommerce_pos_staff_pick_nonce' ); ?>
		<p class="submit">
			<?php submit_button( __( 'Continue', 'woocommerce' ), 'primary', 'pick_pos_staff', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>
