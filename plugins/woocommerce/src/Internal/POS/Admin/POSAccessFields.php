<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WP_Error;

/**
 * Renders + validates + persists the shared POS preset + PIN field group.
 *
 * Three callsites use this:
 *  - UserFormIntegration: rendered inside wp-admin → Users → Add New, validated
 *    in user_profile_update_errors, persisted in user_register.
 *  - The Staff edit screen: rendered in html-pos-staff-edit.php, persisted by
 *    WC_Admin_POS_Staff::save() for both the "grant access" and "edit" branches.
 *  - The Staff picker page (to be added): used to route the admin into the
 *    edit screen for an existing user.
 *
 * The class is stateless — every method is static and takes only the inputs
 * it needs. The render() method outputs the field rows but not the surrounding
 * <h2> / <table>, since each context wraps them differently.
 *
 * @since 11.0.0
 * @internal
 */
class POSAccessFields {

	public const FIELD_PRESET = 'pos_preset';
	public const FIELD_PIN    = 'pos_pin';

	/**
	 * Echo the preset dropdown and PIN field as table rows.
	 *
	 * The caller is responsible for the surrounding <table class="form-table">
	 * (or other wrapper) and any context-specific labelling.
	 *
	 * @param string $current_preset Currently-selected preset slug (empty for "no selection").
	 * @param bool   $pin_optional   When true, the PIN input is not required and the
	 *                                description reads "Leave blank to keep". Use this
	 *                                for editing an existing POS user who already has
	 *                                a PIN set.
	 * @param bool   $has_pin        When $pin_optional is true, whether the user has a
	 *                                PIN already (affects the label: "Reset PIN" vs
	 *                                "Set PIN"). Ignored when $pin_optional is false.
	 */
	public static function render( string $current_preset = '', bool $pin_optional = false, bool $has_pin = false ): void {
		$presets = Capabilities::assignable_pos_presets();
		?>
		<tr class="form-field<?php echo $pin_optional ? '' : ' form-required'; ?>">
			<th scope="row">
				<label for="<?php echo esc_attr( self::FIELD_PRESET ); ?>">
					<?php esc_html_e( 'POS role', 'woocommerce' ); ?>
					<?php if ( ! $pin_optional ) : ?>
						<span class="description"><?php esc_html_e( '(required)', 'woocommerce' ); ?></span>
					<?php endif; ?>
				</label>
			</th>
			<td>
				<select id="<?php echo esc_attr( self::FIELD_PRESET ); ?>" name="<?php echo esc_attr( self::FIELD_PRESET ); ?>" required>
					<?php if ( '' === $current_preset ) : ?>
						<option value=""><?php esc_html_e( '— Select a role —', 'woocommerce' ); ?></option>
					<?php endif; ?>
					<?php foreach ( $presets as $preset_value ) : ?>
						<option value="<?php echo esc_attr( $preset_value ); ?>"<?php selected( $preset_value, $current_preset ); ?>>
							<?php echo esc_html( Capabilities::preset_label( $preset_value ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="form-field<?php echo $pin_optional ? '' : ' form-required'; ?>">
			<th scope="row">
				<label for="<?php echo esc_attr( self::FIELD_PIN ); ?>">
					<?php
					if ( $pin_optional ) {
						echo esc_html( $has_pin ? __( 'Reset PIN', 'woocommerce' ) : __( 'Set PIN', 'woocommerce' ) );
					} else {
						esc_html_e( 'PIN', 'woocommerce' );
						echo ' <span class="description">' . esc_html__( '(required)', 'woocommerce' ) . '</span>';
					}
					?>
				</label>
			</th>
			<td>
				<input
					type="password"
					id="<?php echo esc_attr( self::FIELD_PIN ); ?>"
					name="<?php echo esc_attr( self::FIELD_PIN ); ?>"
					pattern="[0-9]*"
					inputmode="numeric"
					minlength="4"
					maxlength="4"
					class="regular-text"
					autocomplete="off"
					<?php echo $pin_optional ? '' : 'required'; ?>
				/>
				<p class="description">
					<?php
					if ( $pin_optional ) {
						echo esc_html(
							$has_pin
								? __( 'Leave blank to keep the existing PIN. Enter exactly 4 digits to replace it.', 'woocommerce' )
								: __( 'Enter exactly 4 digits.', 'woocommerce' )
						);
					} else {
						esc_html_e( 'Required. Enter exactly 4 digits. Each staff member must have a unique PIN — it identifies them in POS mode in the WooCommerce mobile apps.', 'woocommerce' );
					}
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Validate the POS preset + PIN inputs.
	 *
	 * Centralizes the "is this a valid preset / PIN to assign" rules so every
	 * callsite (user-new.php hook, staff page save handler, future granular
	 * UIs) accepts and rejects the same way.
	 *
	 * @param POSPinService $pin_service       PIN service used for uniqueness checks.
	 * @param string        $preset            Submitted preset slug.
	 * @param string        $pin               Submitted PIN string (may be empty when
	 *                                          $pin_optional is true and the admin chose
	 *                                          to keep the existing PIN).
	 * @param int           $exclude_user_id   When non-zero, exclude this user from PIN
	 *                                          uniqueness scans — for an edit flow where
	 *                                          the user is keeping/changing their own PIN.
	 * @param bool          $pin_optional      When true, a blank PIN is accepted (means
	 *                                          "keep the existing PIN").
	 * @return WP_Error|null WP_Error on failure; null on success.
	 */
	public static function validate(
		POSPinService $pin_service,
		string $preset,
		string $pin,
		int $exclude_user_id = 0,
		bool $pin_optional = false
	): ?WP_Error {
		if ( ! in_array( $preset, Capabilities::assignable_pos_presets(), true ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_preset',
				__( 'Please choose a valid POS role.', 'woocommerce' )
			);
		}

		if ( '' === $pin && $pin_optional ) {
			return null;
		}

		if ( ! $pin_service->validate_pin_format( $pin ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_pin_format',
				__( 'PIN must be exactly 4 digits.', 'woocommerce' )
			);
		}

		if ( $pin_service->is_pin_used_by_other_user( $pin, $exclude_user_id ) ) {
			return new WP_Error(
				'woocommerce_pos_pin_in_use',
				__( 'This PIN is already in use by another staff member. Choose a different PIN.', 'woocommerce' )
			);
		}

		return null;
	}

	/**
	 * Apply the validated preset + PIN to a user.
	 *
	 * Sets the PIN first so a uniqueness collision at write time (race with a
	 * concurrent admin add) doesn't leave the user with a preset and no PIN.
	 * The caller should have already run validate() — this method only re-checks
	 * the rules that could change between validate-time and persist-time.
	 *
	 * @param POSPinService $pin_service PIN service used to persist the PIN.
	 * @param int           $user_id     Target user.
	 * @param string        $preset      Validated preset slug.
	 * @param string        $pin         Validated PIN string, or empty to leave the
	 *                                    existing PIN untouched.
	 * @return WP_Error|null WP_Error on PIN write failure; null on success.
	 */
	public static function persist(
		POSPinService $pin_service,
		int $user_id,
		string $preset,
		string $pin
	): ?WP_Error {
		if ( ! in_array( $preset, Capabilities::assignable_pos_presets(), true ) ) {
			return new WP_Error(
				'woocommerce_pos_invalid_preset',
				__( 'Please choose a valid POS role.', 'woocommerce' )
			);
		}

		if ( '' !== $pin ) {
			$pin_result = $pin_service->set_pin( $user_id, $pin );
			if ( is_wp_error( $pin_result ) ) {
				return $pin_result;
			}
		}

		Capabilities::set_pos_preset( $user_id, $preset );
		return null;
	}
}
