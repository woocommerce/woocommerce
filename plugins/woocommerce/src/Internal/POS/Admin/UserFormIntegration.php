<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WP_Error;

/**
 * Bridges POS staff management into the standard wp-admin → Users → Add New form.
 *
 * The POS Staff page's "Add staff" button deep-links to
 * user-new.php?pos_staff=1&role=pos_staff, and this class adds the POS preset
 * + PIN fields to that screen, validates them before the user is inserted, and
 * persists them once the user exists. The standard user-new form is untouched
 * outside this flagged request.
 *
 * The validation step runs in `user_profile_update_errors`, which fires before
 * wp_insert_user — so a bad PIN or missing preset blocks the create entirely
 * instead of producing a half-configured orphan account.
 *
 * @since 11.0.0
 * @internal
 */
class UserFormIntegration {

	public const REQUEST_FLAG_PARAM = 'pos_staff';
	public const NONCE_FIELD_NAME   = 'woocommerce_pos_user_new_nonce';
	public const NONCE_ACTION       = 'woocommerce-pos-user-new';

	/**
	 * PIN service used for PIN validation and persistence.
	 *
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * Set once apply_pos_settings() successfully persists a POS preset, so the
	 * wp_redirect filter knows the current request was a POS add and should
	 * land the admin back on the Staff page instead of the default users.php.
	 *
	 * @var bool
	 */
	private bool $redirect_to_staff_after_add = false;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param POSPinService $pin_service The PIN service.
	 */
	final public function init( POSPinService $pin_service ): void {
		$this->pin_service = $pin_service;
	}

	/**
	 * Register the user-new.php hooks.
	 *
	 * @internal
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_action( 'user_new_form', array( $this, 'render_user_new_form' ) );
		add_action( 'user_profile_update_errors', array( $this, 'validate_profile_errors' ), 10, 3 );
		add_action( 'user_register', array( $this, 'apply_pos_settings' ) );
		add_filter( 'wp_redirect', array( $this, 'filter_post_add_redirect' ) );
		add_filter( 'pre_option_default_role', array( $this, 'filter_default_role' ) );
	}

	/**
	 * Whether the current request is the POS-flagged add-staff flow.
	 *
	 * Honored both as a GET param (the Staff page deep-links to user-new.php
	 * with ?pos_staff=1) and as a POST input (carried via a hidden field on
	 * resubmit so the flag survives validation errors).
	 *
	 * @return bool
	 */
	private function is_pos_staff_request(): bool {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if (
			isset( $_GET[ self::REQUEST_FLAG_PARAM ] )
			&& '1' === sanitize_text_field( wp_unslash( $_GET[ self::REQUEST_FLAG_PARAM ] ) )
		) {
			return true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if (
			isset( $_POST[ self::REQUEST_FLAG_PARAM ] )
			&& '1' === sanitize_text_field( wp_unslash( $_POST[ self::REQUEST_FLAG_PARAM ] ) )
		) {
			return true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return false;
	}

	/**
	 * Render the POS preset + PIN fields on the wp-admin Add New User form.
	 *
	 * Hooked on `user_new_form`, which fires inside the form for both the
	 * "Add new user" and "Add existing user" sections of user-new.php — only
	 * the new-user section is the POS create flow, so we gate on the `$type`
	 * argument.
	 *
	 * @internal
	 *
	 * @param string $type The form section identifier ('add-new-user' or 'add-existing-user').
	 */
	public function render_user_new_form( string $type ): void {
		if ( 'add-new-user' !== $type ) {
			return;
		}
		if ( ! $this->is_pos_staff_request() ) {
			return;
		}
		if ( ! current_user_can( 'create_users' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$current_preset = isset( $_POST[ POSAccessFields::FIELD_PRESET ] )
			? sanitize_key( wp_unslash( $_POST[ POSAccessFields::FIELD_PRESET ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		?>
		<h2 id="pos-access-section"><?php esc_html_e( 'POS access', 'woocommerce' ); ?></h2>
		<input type="hidden" name="<?php echo esc_attr( self::REQUEST_FLAG_PARAM ); ?>" value="1" />
		<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD_NAME ); ?>

		<table class="form-table" role="presentation">
			<?php POSAccessFields::render( $current_preset ); ?>
		</table>
		<?php
	}

	/**
	 * Validate POS preset + PIN inputs before the WP user is inserted.
	 *
	 * Hooked on `user_profile_update_errors`, which fires inside edit_user()
	 * before wp_insert_user. Adding an error to the WP_Error aborts the create
	 * so we never end up with an orphan WP user whose POS section couldn't be
	 * persisted.
	 *
	 * @internal
	 *
	 * @param WP_Error        $errors WP_Error to populate with validation errors.
	 * @param bool            $update True when editing an existing user; we skip these.
	 * @param \stdClass|mixed $user New user data (\stdClass on create; \WP_User on update).
	 */
	public function validate_profile_errors( WP_Error $errors, bool $update, $user ): void {
		unset( $user );
		if ( $update ) {
			return;
		}
		if ( ! $this->is_pos_staff_request() ) {
			return;
		}

		if ( ! $this->verify_nonce() ) {
			$errors->add(
				'woocommerce_pos_invalid_nonce',
				__( 'POS form session expired. Please try again.', 'woocommerce' )
			);
			return;
		}

		list( $preset, $pin ) = $this->read_submitted_fields();

		$error = POSAccessFields::validate( $this->pin_service, $preset, $pin );
		if ( $error instanceof WP_Error ) {
			foreach ( $error->get_error_codes() as $code ) {
				$errors->add( $code, $error->get_error_message( $code ) );
			}
		}
	}

	/**
	 * Apply the POS preset + PIN once the WP user has been inserted.
	 *
	 * Hooked on `user_register`, which fires after wp_insert_user. Validation
	 * has already run in validate_profile_errors(); the only remaining failure
	 * mode is a PIN that became taken between validation and write (concurrent
	 * admin add). On PIN failure we leave the account without POS access — the
	 * admin can finish setup from the Staff page edit screen — rather than
	 * applying a half-configured preset.
	 *
	 * @internal
	 *
	 * @param int $user_id ID of the user that was just inserted.
	 */
	public function apply_pos_settings( int $user_id ): void {
		if ( ! $this->is_pos_staff_request() ) {
			return;
		}
		if ( ! $this->verify_nonce() ) {
			return;
		}

		list( $preset, $pin ) = $this->read_submitted_fields();

		$result = POSAccessFields::persist( $this->pin_service, $user_id, $preset, $pin );
		if ( null !== $result ) {
			return;
		}

		$this->redirect_to_staff_after_add = true;
	}

	/**
	 * Verify the POS section nonce on the current request.
	 *
	 * @return bool True when a valid nonce is present.
	 */
	private function verify_nonce(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- This IS the nonce check.
		$nonce = isset( $_POST[ self::NONCE_FIELD_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD_NAME ] ) ) : '';
		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Read the submitted POS preset + PIN from the request.
	 *
	 * Callers must verify the nonce first (see verify_nonce()).
	 *
	 * @return array{0:string,1:string} [ preset, pin ].
	 */
	private function read_submitted_fields(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by the caller via verify_nonce().
		$preset = isset( $_POST[ POSAccessFields::FIELD_PRESET ] )
			? sanitize_key( wp_unslash( $_POST[ POSAccessFields::FIELD_PRESET ] ) )
			: '';
		$pin    = isset( $_POST[ POSAccessFields::FIELD_PIN ] )
			? sanitize_text_field( wp_unslash( $_POST[ POSAccessFields::FIELD_PIN ] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return array( $preset, $pin );
	}

	/**
	 * Pre-select the `pos_staff` role in the wp-admin Add New User dropdown.
	 *
	 * The wp-admin user-new.php screen only honors `?role=` after a nonced
	 * re-submit, so on the initial GET it pre-selects whatever
	 * `get_option( 'default_role' )` returns (Subscriber out of the box).
	 * Short-circuit that lookup so the dropdown lands on the POS staff label
	 * when the admin enters via the Staff page.
	 *
	 * The filter is scoped to the POS-flagged request so it cannot leak into
	 * unrelated `default_role` reads elsewhere in the same admin session.
	 *
	 * @internal
	 *
	 * @param mixed $pre Filtered value (`false` means "fall through to the option lookup").
	 * @return mixed
	 */
	public function filter_default_role( $pre ) {
		if ( ! $this->is_pos_staff_request() ) {
			return $pre;
		}
		return Capabilities::POS_STAFF_ROLE;
	}

	/**
	 * Redirect back to the POS Staff list after a successful add.
	 *
	 * The wp-admin user-new.php screen hardcodes a redirect to users.php?update=add
	 * at the end of the create flow; intercept it on the POS path and send the
	 * admin back to the Staff page (which already renders the "Staff added" notice
	 * on ?added=1).
	 *
	 * @internal
	 *
	 * @param string $location Redirect target proposed by core or the previous filter.
	 * @return string
	 */
	public function filter_post_add_redirect( $location ): string {
		if ( ! $this->redirect_to_staff_after_add ) {
			return (string) $location;
		}
		$this->redirect_to_staff_after_add = false;
		return admin_url( 'admin.php?page=wc-settings&tab=point-of-sale&section=staff&added=1' );
	}
}
