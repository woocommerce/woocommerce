<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification\Admin;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an "Email address verified" control to the wp-admin user profile screen.
 *
 * The verification meta is the source of truth; this checkbox simply reflects and edits it.
 * Ticking it marks the user's current email verified (which also links any matching past guest
 * orders); unticking it clears the status.
 *
 * @since 11.0.0
 */
class UserProfileField {

	private const NONCE_ACTION = 'wc_email_verified';
	private const FIELD        = 'wc_email_verified';

	/**
	 * Verification service.
	 *
	 * @var EmailVerificationService
	 */
	private $service;

	/**
	 * Constructor. Registers hooks.
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'render' ) );
		add_action( 'edit_user_profile', array( $this, 'render' ) );
		// Saved on profile_update (not the *_profile_update hooks) because that fires after the user
		// record is written, so an email changed in the same save is already current when we verify it.
		add_action( 'profile_update', array( $this, 'save' ) );
	}

	/**
	 * Inject dependencies.
	 *
	 * @internal
	 *
	 * @param EmailVerificationService $service Verification service.
	 */
	final public function init( EmailVerificationService $service ): void {
		$this->service = $service;
	}

	/**
	 * Render the "Email address verified" checkbox.
	 *
	 * @param WP_User|mixed $user The user being edited.
	 */
	public function render( $user ): void {
		if ( ! $user instanceof WP_User || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION . '_' . $user->ID, self::NONCE_ACTION . '_nonce' );
		?>
		<h2><?php esc_html_e( 'Email confirmation', 'woocommerce' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Email address confirmed', 'woocommerce' ); ?></th>
				<td>
					<label for="<?php echo esc_attr( self::FIELD ); ?>">
						<input type="checkbox" name="<?php echo esc_attr( self::FIELD ); ?>" id="<?php echo esc_attr( self::FIELD ); ?>" value="1" <?php checked( $this->service->is_verified( $user->ID ) ); ?> />
						<?php esc_html_e( 'The customer has confirmed they own this email address.', 'woocommerce' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Confirming the email address also links any past guest orders placed with it to this account.', 'woocommerce' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Persist the "Email address verified" checkbox when a profile is saved.
	 *
	 * @param int|mixed $user_id The user being saved.
	 */
	public function save( $user_id ): void {
		$user_id = (int) $user_id;

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_ACTION . '_nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_ACTION . '_nonce' ] ) ) : '';

		// profile_update fires on every wp_update_user() call, so the nonce is what scopes us to a
		// profile-screen submission where our field was actually rendered; without it we would clear
		// verification on every unrelated user update.
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION . '_' . $user_id ) ) {
			return;
		}

		if ( isset( $_POST[ self::FIELD ] ) ) {
			$this->service->mark_verified( $user_id );
		} else {
			$this->service->clear_verification( $user_id );
		}
	}
}
