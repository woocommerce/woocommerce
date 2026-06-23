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
 */

if ( ! isset( $has_pos_access, $has_pin, $user_id, $user, $current_pos_preset ) || ! $user instanceof WP_User ) {
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
	<h2><?php echo esc_html( $heading_text ); ?></h2>

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
				<?php POSAccessFields::render( $current_pos_preset, $has_pos_access, $has_pin ); ?>
			</tbody>
		</table>

		<?php wp_nonce_field( 'woocommerce-pos-staff-edit', 'woocommerce_pos_staff_nonce' ); ?>
		<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user_id ); ?>" />
		<p class="submit">
			<?php submit_button( $submit_label, 'primary', 'save_pos_staff', false ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff' ) ); ?>">
				<?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
			</a>
		</p>
	</form>
</div>
