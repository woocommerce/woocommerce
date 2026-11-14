<?php
/**
 * Base test case for the UCP cart routes.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\Ucp\AbstractCartRoute;

/**
 * Enables the UCP routes for each test and undoes the session binding they leave behind.
 */
abstract class UcpCartTestCase extends ControllerTestCase {
	/**
	 * Option behind the `ucp` feature.
	 */
	protected const FEATURE_OPTION = 'woocommerce_feature_ucp_enabled';

	/**
	 * The session, customer and cart the harness set up, so tearDown can put them back
	 * after a route binds its own.
	 *
	 * @var array{session: \WC_Session|null, customer: \WC_Customer|null, cart: \WC_Cart|null, loaded: int|null, totals: int|null}
	 */
	private $wc_globals_backup = array();

	/**
	 * Enable the UCP routes and rebuild the REST server so they register.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Inside the per-test transaction, so the rollback turns the flag off again;
		// the routes register on the rebuilt server.
		update_option( self::FEATURE_OPTION, 'yes' );
		$this->initialize_store_api_server();

		// A per-test DB transaction rolls back rows but not the WC() singletons, so a route
		// that swaps them would leak into the next test. Keep the originals to restore.
		$this->wc_globals_backup = array(
			'session'  => WC()->session,
			'customer' => WC()->customer,
			'cart'     => WC()->cart,
			'loaded'   => $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] ?? null,
			'totals'   => $GLOBALS['wp_actions']['woocommerce_after_calculate_totals'] ?? null,
		);
	}

	/**
	 * Restore the cancellation marker and the WC() singletons.
	 */
	protected function tearDown(): void {
		unset( $_SERVER['HTTP_CART_TOKEN'] );

		if ( WC()->session ) {
			WC()->session->set( AbstractCartRoute::CANCELLED_SESSION_KEY, null );
		}

		if ( WC()->cart instanceof \WC_Cart ) {
			\WC_Cart_Session::set_updates_enabled_for_cart( WC()->cart, true );
		}

		// Undo the route's swap so the next test gets the cart, session and customer the
		// harness built, not the token-bound one left behind here.
		WC()->session  = $this->wc_globals_backup['session'];
		WC()->customer = $this->wc_globals_backup['customer'];
		WC()->cart     = $this->wc_globals_backup['cart'];
		$markers       = array(
			'woocommerce_load_cart_from_session' => 'loaded',
			'woocommerce_after_calculate_totals' => 'totals',
		);
		foreach ( $markers as $action => $key ) {
			if ( null === $this->wc_globals_backup[ $key ] ) {
				unset( $GLOBALS['wp_actions'][ $action ] );
			} else {
				$GLOBALS['wp_actions'][ $action ] = $this->wc_globals_backup[ $key ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the per-process markers the harness cleared.
			}
		}

		parent::tearDown();
	}
}
