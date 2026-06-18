<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Store API Context primitive.
 *
 * In the shared-routes design a request is POS only when the `point_of_sale`
 * feature is on, the client explicitly marked it as POS (X-WooCommerce-POS
 * header or `pos` param), the request targets a Store API cart/checkout route,
 * AND the caller can `manage_woocommerce`. Detection is also latched: the first
 * positive verdict sticks for the rest of the request so the mid-request guest
 * swap can't turn it off.
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
	 * Backup of the POS marker header to restore in tearDown.
	 *
	 * @var string|null
	 */
	private $original_marker_header;

	/**
	 * Backup of $_GET['rest_route'] to restore in tearDown.
	 *
	 * @var string|null
	 */
	private $original_rest_route;

	/**
	 * Backup of $_GET['pos'] to restore in tearDown.
	 *
	 * @var string|null
	 */
	private $original_pos_param;

	/**
	 * Original current user to restore in tearDown.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Admin user with manage_woocommerce.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up — back up superglobals/user we may mutate and enable POS.
	 */
	public function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->original_request_uri = $_SERVER['REQUEST_URI'] ?? null;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->original_marker_header = $_SERVER['HTTP_X_WOOCOMMERCE_POS'] ?? null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$this->original_rest_route = $_GET['rest_route'] ?? null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$this->original_pos_param = $_GET['pos'] ?? null;
		$this->original_user_id   = get_current_user_id();
		unset( $_GET['rest_route'], $_GET['pos'], $_SERVER['HTTP_X_WOOCOMMERCE_POS'] );

		$this->admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );

		// Detection requires the feature on; force it for these tests.
		update_option( 'woocommerce_feature_point_of_sale_enabled', 'yes' );
		// Clear any latched verdict from a previous test in this process.
		Context::set_test_override( null );
	}

	/**
	 * Restore mutated state.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		delete_option( 'woocommerce_feature_point_of_sale_enabled' );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );

		$this->restore_superglobal( $_SERVER, 'REQUEST_URI', $this->original_request_uri );
		$this->restore_superglobal( $_SERVER, 'HTTP_X_WOOCOMMERCE_POS', $this->original_marker_header );
		$this->restore_superglobal( $_GET, 'rest_route', $this->original_rest_route );
		$this->restore_superglobal( $_GET, 'pos', $this->original_pos_param );

		parent::tearDown();
	}

	/**
	 * Restore (or unset) a single superglobal key.
	 *
	 * @param array       $superglobal Reference to $_SERVER or $_GET.
	 * @param string      $key         Key to restore.
	 * @param string|null $original    Original value, or null to unset.
	 */
	private function restore_superglobal( array &$superglobal, string $key, $original ): void {
		if ( null === $original ) {
			unset( $superglobal[ $key ] );
		} else {
			$superglobal[ $key ] = $original;
		}
	}

	/**
	 * Mark the request as POS (default to the header).
	 */
	private function set_pos_marker(): void {
		$_SERVER['HTTP_X_WOOCOMMERCE_POS'] = '1';
	}

	/**
	 * @testdox is_pos_request returns true for a marked manager request on a cart route.
	 */
	public function test_returns_true_for_marked_manager_on_cart_route(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$this->set_pos_marker();

		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true for a marked manager request on the checkout route.
	 */
	public function test_returns_true_for_marked_manager_on_checkout_route(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';
		$this->set_pos_marker();

		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * The core reason the marker exists: a store manager checking out on the web
	 * (same route, same capability, but no POS marker) must NOT be treated as POS.
	 *
	 * @testdox is_pos_request returns false for a manager on the checkout route WITHOUT the POS marker.
	 */
	public function test_returns_false_for_manager_without_marker(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';

		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true when the marker arrives as the `pos` query parameter.
	 */
	public function test_returns_true_for_pos_query_param_marker(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$_GET['pos']            = '1';

		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for a falsy `pos` query parameter.
	 */
	public function test_returns_false_for_falsy_pos_query_param(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$_GET['pos']            = '0';

		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for an unauthenticated shopper even with the marker.
	 */
	public function test_returns_false_for_guest_even_with_marker(): void {
		wp_set_current_user( 0 );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$this->set_pos_marker();

		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false when the point_of_sale feature is disabled.
	 */
	public function test_returns_false_when_feature_disabled(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart/add-item';
		$this->set_pos_marker();
		update_option( 'woocommerce_feature_point_of_sale_enabled', 'no' );

		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns false for a marked manager request on a non cart/checkout route.
	 */
	public function test_returns_false_for_non_cart_route(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/products';
		$this->set_pos_marker();

		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox is_pos_request returns true when the route arrives via the rest_route GET parameter (proxied/Jetpack tunnel).
	 */
	public function test_returns_true_for_rest_route_get_parameter(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/?rest_route=/wc/store/v1/checkout&pos=1';
		$_GET['rest_route']     = '/wc/store/v1/checkout';
		$_GET['pos']            = '1';

		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox a positive verdict is latched so a later guest swap can't turn POS off.
	 */
	public function test_verdict_is_latched_across_user_swap(): void {
		wp_set_current_user( $this->admin_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';
		$this->set_pos_marker();

		// First evaluation latches true while the manager is authenticated.
		$this->assertTrue( Context::is_pos_request() );

		// CurrentUserSwap would drop the user to a guest mid-request; the verdict
		// must survive that.
		wp_set_current_user( 0 );
		$this->assertTrue( Context::is_pos_request() );
	}

	/**
	 * @testdox test override takes precedence over live detection.
	 */
	public function test_override_takes_precedence(): void {
		wp_set_current_user( 0 );
		// No marker, no manager — live detection would be false.
		Context::set_test_override( true );
		$this->assertTrue( Context::is_pos_request() );

		Context::set_test_override( false );
		$this->assertFalse( Context::is_pos_request() );
	}

	/**
	 * @testdox clearing the override drops the latched verdict.
	 */
	public function test_clearing_override_resets_latch(): void {
		Context::set_test_override( true );
		$this->assertTrue( Context::is_pos_request() );

		// Clearing must reset the latch so the next request starts fresh; with a
		// guest, no marker and no POS URI, detection is now false.
		Context::set_test_override( null );
		wp_set_current_user( 0 );
		unset( $_SERVER['REQUEST_URI'], $_SERVER['HTTP_X_WOOCOMMERCE_POS'] );
		$this->assertFalse( Context::is_pos_request() );
	}
}
