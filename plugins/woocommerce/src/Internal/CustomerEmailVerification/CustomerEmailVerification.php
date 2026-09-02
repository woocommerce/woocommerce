<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\Admin\UserProfileField;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\CustomerVerifyEmail;

/**
 * Boot class for the customer email verification subsystem.
 *
 * Resolves each controller so that their constructors register hooks during the
 * plugins_loaded action.
 *
 * @since 11.0.0
 */
class CustomerEmailVerification {

	/**
	 * Initialize the subsystem.
	 *
	 * @since 11.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
	}

	/**
	 * Resolve all subsystem controllers so their constructors register hooks.
	 *
	 * @internal
	 * @since 11.0.0
	 */
	public function init_hooks(): void {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email_classes' ) );

		// Link a customer's matching guest orders to their account once they verify their email.
		// wc_update_new_customer_past_orders() casts the ID and no-ops for guest/invalid users; the
		// order count it returns is unused here.
		// @phpstan-ignore-next-line return.void -- The returned count is intentionally discarded.
		add_action( 'woocommerce_customer_email_verified', 'wc_update_new_customer_past_orders' );

		$container = wc_get_container();
		$container->get( VerificationController::class );
		$container->get( VerificationEventListener::class );

		if ( is_admin() ) {
			$container->get( UserProfileField::class );
		}
	}

	/**
	 * Register the customer email verification email with WooCommerce.
	 *
	 * @internal
	 *
	 * @param array $emails Registered email classes.
	 * @return array
	 */
	public function register_email_classes( array $emails ): array {
		$emails['WC_Email_Customer_Verify_Email'] = new CustomerVerifyEmail();
		return $emails;
	}
}
