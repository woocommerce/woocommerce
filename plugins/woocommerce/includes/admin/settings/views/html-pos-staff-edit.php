<?php
/**
 * Admin view: set up POS access for a user.
 *
 * Renders the same preset + PIN form for both:
 *  - Editing an existing POS staff member (PIN optional; "leave blank to keep").
 *  - Granting POS access to a user who doesn't have it yet (PIN required).
 *
 * @package WooCommerce\Admin\Settings
 * @since   11.0.0
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\POS\Admin\POSAccessFields;
use Automattic\WooCommerce\Internal\POS\Capabilities;

defined( 'ABSPATH' ) || exit;

/*
 * Template variables passed from WC_Admin_POS_Staff::edit_output().
 *
 * @var bool    $has_pos_access     Whether the target user currently has POS access.
 *                                   Controls grant vs edit copy + PIN required-ness.
 * @var bool    $has_pin            Whether the user already has a PIN (only meaningful
 *                                   when $has_pos_access is true).
 * @var int     $user_id            User ID.
 * @var WP_User $user               User object.
 * @var string  $current_pos_preset Current POS preset meta value (empty for grant flow).
 * @var string  $form_action_url    URL the form posts back to (this same edit screen).
 * @var string  $list_url           Staff list URL for the back + cancel links.
 */

if ( ! isset( $has_pos_access, $has_pin, $user_id, $user, $current_pos_preset, $form_action_url, $list_url ) || ! $user instanceof WP_User ) {
	return;
}

$heading_text = $has_pos_access
	? __( 'Edit staff', 'woocommerce' )
	: sprintf(
		/* translators: %s: user display name. */
		__( 'Grant POS access to %s', 'woocommerce' ),
		$user->display_name
	);

$submit_label = $has_pos_access
	? __( 'Save', 'woocommerce' )
	: __( 'Grant access', 'woocommerce' );
?>

<div id="pos-staff-fields" class="settings-panel">
	<?php wc_back_header( $heading_text, __( 'Back to staff', 'woocommerce' ), $list_url ); ?>

	<form method="post" action="<?php echo esc_url( $form_action_url ); ?>">
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="wc-pos-staff-user-row">
					<th scope="row" class="titledesc">
						<?php esc_html_e( 'User', 'woocommerce' ); ?>
					</th>
					<td class="forminp">
						<strong><?php echo esc_html( $user->display_name ); ?></strong>
						<span class="description">(<?php echo esc_html( $user->user_email ); ?>)</span>
					</td>
				</tr>
				<?php POSAccessFields::render( $current_pos_preset, $has_pos_access, $has_pin ); ?>
			</tbody>
		</table>

		<?php wp_nonce_field( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user_id ); ?>" />
		<p class="submit">
			<?php submit_button( $submit_label, 'primary', 'save_pos_staff', false ); ?>
			<a class="button" href="<?php echo esc_url( $list_url ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>

		<?php
		/*
		 * ----------------------------------------------------------------------
		 * DEBUG ONLY — remove before release.
		 *
		 * Per-capability checkboxes for toggling individual woocommerce_pos_* caps
		 * on this user, to test client behavior when a staff member is missing a
		 * specific cap. Saving writes the caps directly via add_cap()/remove_cap()
		 * to match exactly what is checked, bypassing the preset bundle above — so
		 * a single cap can be turned off without dropping the others. The preset
		 * meta is left as-is, so the role label may no longer match the actual
		 * caps; that is expected for this tool. `formnovalidate` lets it post
		 * without the required role/PIN fields. Gated behind WP_DEBUG so it never
		 * renders in production (the whole Staff UI is also behind the
		 * point_of_sale_staff dev feature flag).
		 * ----------------------------------------------------------------------
		 */
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) :
			?>
			<div class="wc-pos-staff-debug" style="margin-top:24px;padding:12px 16px;border:1px dashed #b32d2e;background:#fcf0f1;max-width:600px;">
				<p style="margin:0 0 6px;font-weight:600;color:#b32d2e;">
					<?php esc_html_e( 'Debug: edit individual POS capabilities (WP_DEBUG only)', 'woocommerce' ); ?>
				</p>
				<p class="description" style="margin:0 0 10px;">
					<?php esc_html_e( 'Toggle individual POS capabilities for this user, independent of the role above. Use it to test client behavior when a staff member lacks a capability. Saving here writes the checked caps exactly as shown.', 'woocommerce' ); ?>
				</p>
				<ul style="margin:0 0 10px;list-style:none;">
					<?php foreach ( Capabilities::all_pos_capabilities() as $debug_cap ) : ?>
						<li style="margin:0 0 4px;">
							<label>
								<input
									type="checkbox"
									name="debug_pos_caps[]"
									value="<?php echo esc_attr( $debug_cap ); ?>"
									<?php checked( user_can( $user_id, $debug_cap ) ); ?>
								/>
								<code><?php echo esc_html( $debug_cap ); ?></code>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
				<button type="submit" name="debug_set_pos_caps" value="1" class="button" formnovalidate>
					<?php esc_html_e( 'Save POS caps (debug)', 'woocommerce' ); ?>
				</button>
			</div>
			<?php
		endif;
		?>
	</form>
</div>
