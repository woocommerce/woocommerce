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
	 * @testdox is_pos_request returns false by default with no POS URI.
	 */
	public function test_returns_false_for_non_pos_request(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true when REQUEST_URI contains the POS prefix.
	 */
	public function test_returns_true_for_pos_uri(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-item';
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for a POS URI when the point_of_sale feature is disabled.
	 */
	public function test_returns_false_when_feature_disabled(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-item';
		update_option( 'woocommerce_feature_point_of_sale_enabled', 'no' );
		try {
			$this->assertFalse(
				Context::is_pos_request(),
				'With point_of_sale off there are no POS routes, so a POS URI must not be treated as a POS request.'
			);
		} finally {
			delete_option( 'woocommerce_feature_point_of_sale_enabled' );
		}
	}

	/**
	 * @testdox is_pos_request returns false for the public POS catalog namespace (not the store-api routes).
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
		$_SERVER['REQUEST_URI'] = '/?rest_route=/wc/internal/pos/v1/checkout';
		$_GET['rest_route']     = '/wc/internal/pos/v1/checkout';
		$this->assertTrue( Context::is_pos_request() );
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

		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-item';
		Context::set_test_override( false );
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox clearing override restores URI detection.
	 */
	public function test_clearing_override_restores_uri_detection(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/internal/pos/v1/cart/add-item';
		Context::set_test_override( false );
		$this->assertFalse( Context::is_pos_request() );

		Context::set_test_override( null );
		$this->assertTrue( Context::is_pos_request() );
	}
}
