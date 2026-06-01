<?php
/**
 * Admin view: add a new POS staff member.
 *
 * Two ways to assign a POS role: search and pick an existing WP user, or leave
 * the search blank and enter a new username (the email is optional — an
 * unreachable placeholder is generated when omitted, which is what we want for
 * POS-only operators who never log into wp-admin).
 *
 * The picker uses the standard wc-customer-search selectWoo widget. The
 * username/email rows are hidden via inline JS once an existing user is
 * selected, mirroring the muxed-form pattern used elsewhere in WC.
 *
 * @package WooCommerce\Admin\Settings
 * @since   10.9.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::add_output().
 *
 * @var string[]      $assignable_pos_roles  List of assignable POS role identifiers.
 * @var string        $assigned_user_ids_csv CSV of user IDs already assigned a POS role.
 * @var array<string, string> $retry_values  Sanitized prior submission, populated when
 *                                           re-rendering the form after a failed submit.
 *                                           Empty array on a fresh open.
 * @var WP_User|null  $retry_existing_user   The previously-chosen existing user, used to
 *                                           pre-render a selected <option> for the
 *                                           wc-customer-search dropdown.
 */

if ( ! isset( $assignable_pos_roles, $assigned_user_ids_csv, $retry_values ) ) {
	return;
}

$retry_user_id        = isset( $retry_values['user_id'] ) ? (int) $retry_values['user_id'] : 0;
$retry_new_user_login = (string) ( $retry_values['new_user_login'] ?? '' );
$retry_new_user_email = (string) ( $retry_values['new_user_email'] ?? '' );
$retry_pos_role       = (string) ( $retry_values['pos_role'] ?? '' );
$form_action_url      = admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff&edit-staff=new' );
?>

<div id="pos-staff-fields" class="settings-panel">
	<h2><?php esc_html_e( 'Add staff', 'woocommerce' ); ?></h2>

	<form method="post" action="<?php echo esc_url( $form_action_url ); ?>">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row" class="titledesc">
						<label for="user_id"><?php esc_html_e( 'Existing user', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<select
							class="wc-customer-search"
							id="user_id"
							name="user_id"
							style="width: 50%;"
							data-placeholder="<?php esc_attr_e( 'Search for an existing user&hellip;', 'woocommerce' ); ?>"
							data-allow_clear="true"
							data-exclude="<?php echo esc_attr( $assigned_user_ids_csv ); ?>">
							<?php if ( $retry_existing_user instanceof WP_User ) : ?>
								<option value="<?php echo esc_attr( (string) $retry_existing_user->ID ); ?>" selected>
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: user display name, 2: user login. */
											__( '%1$s (%2$s)', 'woocommerce' ),
											$retry_existing_user->display_name,
											$retry_existing_user->user_login
										)
									);
									?>
								</option>
							<?php endif; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Optional. Search by name, email, or login. Leave blank to create a new user below.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wc-pos-staff-new-user-row">
					<th scope="row" class="titledesc">
						<label for="new_user_login"><?php esc_html_e( 'New username', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<input
							type="text"
							id="new_user_login"
							name="new_user_login"
							class="regular-text"
							autocomplete="off"
							value="<?php echo esc_attr( $retry_new_user_login ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'Required when no existing user is selected. Used for order attribution in wp-admin.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wc-pos-staff-new-user-row">
					<th scope="row" class="titledesc">
						<label for="new_user_email"><?php esc_html_e( 'Email', 'woocommerce' ); ?></label>
					</th>
					<td class="forminp">
						<input
							type="email"
							id="new_user_email"
							name="new_user_email"
							class="regular-text"
							autocomplete="off"
							value="<?php echo esc_attr( $retry_new_user_email ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'Optional. If left blank, a plus-addressed alias of your store email is generated (e.g. merchant+pos-42@example.com), so any password-reset emails reach you rather than the staff member.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row" class="titledesc">
						<label for="pos_role"><?php esc_html_e( 'POS role', 'woocommerce' ); ?> <span class="description">*</span></label>
					</th>
					<td class="forminp">
						<select id="pos_role" name="pos_role" required>
							<option value=""><?php esc_html_e( '— Select a role —', 'woocommerce' ); ?></option>
							<?php foreach ( $assignable_pos_roles as $role_value ) : ?>
								<option value="<?php echo esc_attr( $role_value ); ?>"<?php selected( $role_value, $retry_pos_role ); ?>>
									<?php echo esc_html( POSCapabilities::role_label( $role_value ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row" class="titledesc">
						<label for="pos_pin"><?php esc_html_e( 'PIN', 'woocommerce' ); ?> <span class="description">*</span></label>
					</th>
					<td class="forminp">
						<input
							type="password"
							id="pos_pin"
							name="pos_pin"
							pattern="[0-9]*"
							inputmode="numeric"
							minlength="4"
							maxlength="4"
							class="input-text"
							autocomplete="off"
							required
						/>
						<p class="description">
							<?php esc_html_e( 'Required. Enter exactly 4 digits. Each staff member must have a unique PIN — it identifies them at the till.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php wp_nonce_field( 'woocommerce-pos-staff-add', 'woocommerce_pos_staff_nonce' ); ?>
		<p class="submit">
			<?php submit_button( __( 'Add staff', 'woocommerce' ), 'primary', 'add_pos_staff', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>

<script>
( function ( $ ) {
	$( function () {
		var $existingUser  = $( '#user_id' );
		var $newUserRows   = $( '.wc-pos-staff-new-user-row' );
		var $newUserInputs = $newUserRows.find( 'input' );

		function syncVisibility() {
			var hasSelection = !! $existingUser.val();
			$newUserRows.toggle( ! hasSelection );
			// Clear any partial input from the hidden rows so the server-side
			// "existing user wins" branch doesn't see stale fields.
			if ( hasSelection ) {
				$newUserInputs.val( '' );
			}
		}

		$existingUser.on( 'change', syncVisibility );
		syncVisibility();
	} );
}( jQuery ) );
</script>
