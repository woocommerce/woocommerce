<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification;

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
		$container = wc_get_container();
		$container->get( VerificationController::class );
		$container->get( LoginGate::class );
		$container->get( OrderLinker::class );
		$container->get( AccountCreationIntegration::class );
	}
}
