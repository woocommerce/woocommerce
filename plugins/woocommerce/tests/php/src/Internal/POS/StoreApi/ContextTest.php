<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Store API Context primitive.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Context
 */
class ContextTest extends WC_Unit_Test_Case {

	/**
	 * Backup of REQUEST_URI to restore in tearDown.
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * Backup of $_GET['rest_route'] to restore in tearDown.
	 *
	 * @var string|null
	 */
	private $original_rest_route;

	/**
	 * Set up — back up superglobals we may mutate.
	 */
	public function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->original_request_uri = $_SERVER['REQUEST_URI'] ?? null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$this->original_rest_route = $_GET['rest_route'] ?? null;
		// Start each test from a clean slate so ambient request state can't leak in.
		unset( $_GET['rest_route'] );
	}

	/**
	 * Restore mutated state.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}
		if ( null === $this->original_rest_route ) {
			unset( $_GET['rest_route'] );
		} else {
			$_GET['rest_route'] = $this->original_rest_route;
		}
		parent::tearDown();
	}

	/**
	 * @testdox is_pos_request returns false for a web Store API URI.
	 */
	public function test_returns_false_for_non_pos_request(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true when the URI path contains the POS prefix.
	 */
	public function test_returns_true_for_pos_uri(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-items';
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true for a subdirectory install path.
	 */
	public function test_returns_true_for_subdirectory_install(): void {
		$_SERVER['REQUEST_URI'] = '/shop/wp-json/wc/internal/pos/v1/cart/add-items';
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * The POS prefix appearing in the query string must not classify a
	 * storefront request as POS — that would silently swap a shopper's session
	 * handler and lose their cart, triggerable by URL alone.
	 *
	 * @testdox is_pos_request ignores the POS prefix inside the query string.
	 */
	public function test_returns_false_when_prefix_only_in_query_string(): void {
		$_SERVER['REQUEST_URI'] = '/?s=wp-json/wc/internal/pos/x';
		$this->assertFalse( Context::is_pos_request() );

		$_SERVER['REQUEST_URI'] = '/shop/?redirect=/wp-json/wc/internal/pos/v1/cart';
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for the public POS catalog namespace.
	 */
	public function test_returns_false_for_pos_catalog_uri(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/pos/v1/catalog/create';
		$this->assertFalse( Context::is_pos_request(), 'The POS catalog feed must not be treated as a POS Store API request.' );
	}

	/**
	 * @testdox is_pos_request returns false when REQUEST_URI is unset.
	 */
	public function test_returns_false_when_request_uri_missing(): void {
		unset( $_SERVER['REQUEST_URI'] );
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true when the route arrives via the rest_route GET parameter (proxied/Jetpack tunnel).
	 */
	public function test_returns_true_for_pos_rest_route_get_parameter(): void {
		// Tunneled requests don't carry the route in the URI path.
		$_SERVER['REQUEST_URI'] = '/?rest_route=/wc/internal/pos/v1/cart/add-items';
		$_GET['rest_route']     = '/wc/internal/pos/v1/cart/add-items';
		$this->assertTrue( Context::is_pos_request() );

		// Some proxies drop the leading slash.
		$_GET['rest_route'] = 'wc/internal/pos/v1/cart/add-items';
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * A rest_route parameter on an admin request (admin-ajax.php never
	 * dispatches REST) is spoofing and must not flip POS policies onto a
	 * shopper's request.
	 *
	 * @testdox is_pos_request ignores rest_route in admin context.
	 */
	public function test_returns_false_for_rest_route_in_admin_context(): void {
		$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php?action=x&rest_route=/wc/internal/pos/v1/cart';
		$_GET['rest_route']     = '/wc/internal/pos/v1/cart';

		set_current_screen( 'index.php' );
		try {
			$this->assertTrue( is_admin(), 'Precondition: admin context.' );
			$this->assertFalse( Context::is_pos_request() );
		} finally {
			set_current_screen( 'front' );
		}

		// Same request state outside admin is legitimate (proxied REST).
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * Detection is memoized per request state; a changed request state must
	 * recompute rather than serve a stale answer.
	 *
	 * @testdox Memoization does not stick across request-state changes.
	 */
	public function test_memoization_follows_request_state(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-items';
		$this->assertTrue( Context::is_pos_request() );
		$this->assertTrue( Context::is_pos_request() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for a non-POS rest_route GET parameter.
	 */
	public function test_returns_false_for_store_rest_route_get_parameter(): void {
		$_SERVER['REQUEST_URI'] = '/?rest_route=/wc/store/v1/cart';
		$_GET['rest_route']     = '/wc/store/v1/cart';
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox test override takes precedence over URI detection.
	 */
	public function test_override_takes_precedence(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		Context::set_test_override( true );
		$this->assertTrue( Context::is_pos_request() );

		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-items';
		Context::set_test_override( false );
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox clearing the override restores URI detection.
	 */
	public function test_clearing_override_restores_uri_detection(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-items';
		Context::set_test_override( false );
		$this->assertFalse( Context::is_pos_request() );

		Context::set_test_override( null );
		$this->assertTrue( Context::is_pos_request() );
	}
}
