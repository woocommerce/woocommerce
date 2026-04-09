<?php
/**
 * Admin view: Edit POS Staff PIN
 *
 * @package WooCommerce\Admin\Settings
 * @since   10.8.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::edit_output().
 *
 * @var bool    $has_pin   Whether the user already has a PIN.
 * @var int     $user_id   User ID.
 * @var WP_User $user      User object.
 * @var string  $role_name Translated role name.
 */

if ( ! isset( $has_pin, $user_id, $user, $role_name ) || ! $user instanceof WP_User ) {
	return;
}
?>

<div id="pos-staff-fields" class="settings-panel">
	<h2>
		<?php
		if ( $has_pin ) {
			esc_html_e( 'Reset PIN', 'woocommerce' );
		} else {
			esc_html_e( 'Set PIN', 'woocommerce' );
		}
		?>
	</h2>

	<form method="post" action="">
		<?php wp_nonce_field( 'woocommerce-pos-staff-pin' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'User', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<strong><?php echo esc_html( $user->display_name ); ?></strong>
						<span class="description">(<?php echo esc_html( $user->user_email ); ?>)</span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'Role', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<?php echo esc_html( $role_name ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="pos_pin"><?php esc_html_e( 'PIN', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<input
							type="password"
							id="pos_pin"
							name="pos_pin"
							pattern="[0-9]*"
							inputmode="numeric"
							minlength="4"
							maxlength="6"
							class="input-text regular-input"
							autocomplete="off"
						/>
						<p class="description">
							<?php esc_html_e( 'Enter a 4-6 digit numeric PIN.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'PIN status', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<?php if ( $has_pin ) : ?>
							<mark class="yes">
								<span class="dashicons dashicons-yes-alt"></span>
								<?php esc_html_e( 'Active', 'woocommerce' ); ?>
							</mark>
						<?php else : ?>
							<mark class="no">&ndash;</mark>
							<?php esc_html_e( 'Not set', 'woocommerce' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<?php
			submit_button(
				__( 'Save PIN', 'woocommerce' ),
				'primary',
				'save_pos_staff_pin',
				false
			);
			?>
			<a
				class="button"
				href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>"
			>
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>
