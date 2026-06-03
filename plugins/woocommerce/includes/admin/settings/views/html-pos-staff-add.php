<?php
/**
 * Admin view: add a new POS staff member.
 *
 * Two ways to assign a POS preset:
 *  - Flow A: search and pick an existing WP user (the user keeps their existing
 *    role; pos_staff is added as a secondary role).
 *  - Flow B: leave the search blank and enter a display name + email to create
 *    a brand-new WP user with pos_staff as their only role.
 *
 * The picker uses the standard wc-customer-search selectWoo widget. The
 * new-user rows are hidden via inline JS once an existing user is selected.
 *
 * @package WooCommerce\Admin\Settings
 * @since   11.0.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\POS\Capabilities as POSCapabilities;

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::add_output().
 *
 * @var string[]              $assignable_pos_presets  List of assignable POS preset identifiers.
 * @var string                $assigned_user_ids_csv   CSV of user IDs already assigned a POS preset.
 * @var array<string, string> $retry_values            Sanitized prior submission, populated when
 *                                                     re-rendering the form after a failed submit.
 * @var WP_User|null          $retry_existing_user     Previously-chosen existing user, used to
 *                                                     pre-render a selected <option> for the picker.
 */

if ( ! isset( $assignable_pos_presets, $assigned_user_ids_csv, $retry_values ) ) {
	return;
}

// add_output() always sets this (to null or a WP_User), but the include() boundary
// hides that fact from static analysis. Normalize here so the view body can rely on it.
$retry_existing_user = $retry_existing_user ?? null;

$retry_user_id          = isset( $retry_values['user_id'] ) ? (int) $retry_values['user_id'] : 0;
$retry_new_display_name = (string) ( $retry_values['new_display_name'] ?? '' );
$retry_new_user_email   = (string) ( $retry_values['new_user_email'] ?? '' );
$retry_pos_preset       = (string) ( $retry_values['pos_preset'] ?? '' );
$form_action_url        = admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff&edit-staff=new' );

/**
 * Human-readable labels for the pos_* capability identifiers, keyed by the
 * cap value. Renders the inline cap matrix that previews what each preset
 * grants.
 */
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
						<label for="new_display_name"><?php esc_html_e( 'Display name', 'woocommerce' ); ?> <span class="description">*</span></label>
					</th>
					<td class="forminp">
						<input
							type="text"
							id="new_display_name"
							name="new_display_name"
							class="regular-text"
							autocomplete="off"
							value="<?php echo esc_attr( $retry_new_display_name ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'Shown in order notes and on the staff list.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr class="wc-pos-staff-new-user-row">
					<th scope="row" class="titledesc">
						<label for="new_user_email"><?php esc_html_e( 'Email', 'woocommerce' ); ?> <span class="description">*</span></label>
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
							<?php esc_html_e( 'Used as the WP account email. A random password is generated — staff sign in with their PIN in POS mode in the WooCommerce mobile apps, not via the WP login screen.', 'woocommerce' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row" class="titledesc">
						<label for="pos_preset"><?php esc_html_e( 'POS role', 'woocommerce' ); ?> <span class="description">*</span></label>
					</th>
					<td class="forminp">
						<select id="pos_preset" name="pos_preset" required>
							<option value=""><?php esc_html_e( '— Select a role —', 'woocommerce' ); ?></option>
							<?php foreach ( $assignable_pos_presets as $preset_value ) : ?>
								<option value="<?php echo esc_attr( $preset_value ); ?>"<?php selected( $preset_value, $retry_pos_preset ); ?>>
									<?php echo esc_html( POSCapabilities::preset_label( $preset_value ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>

						<div class="wc-pos-staff-cap-preview" hidden>
							<p class="description"><?php esc_html_e( 'Permissions granted by this role:', 'woocommerce' ); ?></p>
							<?php foreach ( $assignable_pos_presets as $preset_value ) : ?>
								<ul
									class="wc-pos-staff-cap-list"
									data-preset="<?php echo esc_attr( $preset_value ); ?>"
									hidden>
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
							<?php esc_html_e( 'Required. Enter exactly 4 digits. Each staff member must have a unique PIN — it identifies them in POS mode in the WooCommerce mobile apps.', 'woocommerce' ); ?>
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
		var $preset        = $( '#pos_preset' );
		var $capPreview    = $( '.wc-pos-staff-cap-preview' );

		function syncVisibility() {
			var hasSelection = !! $existingUser.val();
			$newUserRows.toggle( ! hasSelection );
			// Clear any partial input from the hidden rows so the server-side
			// "existing user wins" branch doesn't see stale fields, and toggle
			// the `required` attribute so the browser only enforces the rule
			// when the field is visible.
			if ( hasSelection ) {
				$newUserInputs.val( '' ).prop( 'required', false );
			} else {
				$newUserInputs.prop( 'required', true );
			}
		}

		function syncCapPreview() {
			var selected = $preset.val();
			$capPreview.find( '.wc-pos-staff-cap-list' ).attr( 'hidden', true );
			if ( selected ) {
				$capPreview.removeAttr( 'hidden' );
				$capPreview.find( '[data-preset="' + selected + '"]' ).removeAttr( 'hidden' );
			} else {
				$capPreview.attr( 'hidden', true );
			}
		}

		$existingUser.on( 'change', syncVisibility );
		$preset.on( 'change', syncCapPreview );
		syncVisibility();
		syncCapPreview();
	} );
}( jQuery ) );
</script>
