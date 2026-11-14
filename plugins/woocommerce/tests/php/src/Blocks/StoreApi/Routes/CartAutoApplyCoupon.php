<?php
/**
 * Controller Tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests for how the Store API treats coupons the store applied automatically.
 *
 * The Store API enforces individual use in CartController rather than going through
 * WC_Cart::apply_coupon(), so the rules that keep a customer from being trapped by an
 * auto-applied coupon have to hold on this path too.
 */
class CartAutoApplyCoupon extends ControllerTestCase {

	/**
	 * Setup test products and coupons.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Auto Apply Test Product',
					'stock_status'  => 'instock',
					'regular_price' => 100,
				)
			),
		);

		wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );
	}

	/**
	 * Clear the cart and the auto-apply lookup between tests.
	 */
	protected function tearDown(): void {
		wc_empty_cart();
		parent::tearDown();
	}

	/**
	 * Apply a coupon the way the Cart and Checkout blocks do.
	 *
	 * @param string $code The coupon code to apply.
	 * @return \WP_REST_Response
	 */
	private function apply_coupon_via_store_api( string $code ) {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'code' => $code ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * @testdox An individual-use auto-apply coupon yields to the coupon the customer applies.
	 */
	public function test_individual_use_auto_apply_coupon_yields_to_customer_coupon() {
		$fixtures = new FixtureData();
		$fixtures->get_coupon(
			array(
				'code'           => 'store_auto_indiv',
				'discount_type'  => 'fixed_cart',
				'amount'         => 7,
				'auto_apply'     => true,
				'individual_use' => true,
			)
		);
		$fixtures->get_coupon(
			array(
				'code'          => 'customer_pick',
				'discount_type' => 'fixed_cart',
				'amount'        => 30,
			)
		);

		wc()->cart->calculate_totals();
		$this->assertTrue( wc()->cart->has_discount( 'store_auto_indiv' ), 'precondition: the auto-apply coupon is applied' );

		// Without the yield this is a 400 the customer cannot act on: they cannot use their own
		// coupon and cannot remove the one blocking it.
		$response = $this->apply_coupon_via_store_api( 'customer_pick' );
		$this->assertEquals( 200, $response->get_status(), 'the customer coupon should be accepted' );

		wc()->cart->calculate_totals();

		$this->assertTrue( wc()->cart->has_discount( 'customer_pick' ), "the customer's coupon should be applied" );
		$this->assertFalse( wc()->cart->has_discount( 'store_auto_indiv' ), 'the individual-use auto-apply coupon should have yielded' );
		$this->assertEqualsWithDelta( 30.0, wc()->cart->get_discount_total(), 0.001, 'only the customer coupon should discount the cart' );
	}

	/**
	 * @testdox An auto-apply coupon yields to an individual-use coupon the customer applies.
	 */
	public function test_auto_apply_coupon_yields_to_individual_use_customer_coupon() {
		$fixtures = new FixtureData();
		$fixtures->get_coupon(
			array(
				'code'          => 'store_auto_plain',
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'auto_apply'    => true,
			)
		);
		$fixtures->get_coupon(
			array(
				'code'           => 'customer_indiv',
				'discount_type'  => 'fixed_cart',
				'amount'         => 25,
				'individual_use' => true,
			)
		);

		wc()->cart->calculate_totals();
		$this->assertTrue( wc()->cart->has_discount( 'store_auto_plain' ), 'precondition: the auto-apply coupon is applied' );

		// The cart's coupon list gets overwritten wholesale further down this route, so the end
		// state alone does not show whether the coupon was actually removed. Extensions listen
		// for this, so the removal has to be a removal and not a silent overwrite.
		$removed = array();
		add_action(
			'woocommerce_removed_coupon',
			function ( $code ) use ( &$removed ) {
				$removed[] = $code;
			}
		);

		$response = $this->apply_coupon_via_store_api( 'customer_indiv' );
		$this->assertEquals( 200, $response->get_status() );

		$this->assertContains( 'store_auto_plain', $removed, 'removing the auto-apply coupon should fire woocommerce_removed_coupon' );

		wc()->cart->calculate_totals();

		$this->assertTrue( wc()->cart->has_discount( 'customer_indiv' ), 'the individual-use coupon should be applied' );
		$this->assertFalse( wc()->cart->has_discount( 'store_auto_plain' ), 'individual use must exclude the auto-apply coupon' );
		$this->assertEqualsWithDelta( 25.0, wc()->cart->get_discount_total(), 0.001, 'the discounts must not stack' );
	}

	/**
	 * @testdox Removing an auto-applied coupon over the Store API is refused with an explanation.
	 */
	public function test_removing_an_auto_applied_coupon_is_refused() {
		$fixtures = new FixtureData();
		$fixtures->get_coupon(
			array(
				'code'          => 'store_auto_fixed',
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'auto_apply'    => true,
			)
		);

		wc()->cart->calculate_totals();
		$this->assertTrue( wc()->cart->has_discount( 'store_auto_fixed' ), 'precondition: the auto-apply coupon is applied' );

		$request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/cart/coupons/store_auto_fixed' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cart_coupon_not_removable', $response->get_data()['code'] );
		$this->assertTrue( wc()->cart->has_discount( 'store_auto_fixed' ), 'the coupon should still be applied' );
	}

	/**
	 * @testdox An auto-apply coupon is reported as not removable in the cart response.
	 */
	public function test_auto_apply_coupon_is_reported_as_not_removable() {
		$fixtures = new FixtureData();
		$fixtures->get_coupon(
			array(
				'code'          => 'store_auto_flagged',
				'discount_type' => 'fixed_cart',
				'amount'        => 10,
				'auto_apply'    => true,
			)
		);
		$fixtures->get_coupon(
			array(
				'code'          => 'store_manual_flagged',
				'discount_type' => 'fixed_cart',
				'amount'        => 5,
			)
		);

		wc()->cart->calculate_totals();
		$this->apply_coupon_via_store_api( 'store_manual_flagged' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$coupons = rest_get_server()->dispatch( $request )->get_data()['coupons'];

		$by_code = array();
		foreach ( $coupons as $coupon ) {
			$by_code[ $coupon['code'] ] = $coupon['is_removable'];
		}

		$this->assertArrayHasKey( 'store_auto_flagged', $by_code );
		$this->assertFalse( $by_code['store_auto_flagged'], 'an auto-apply coupon is not removable' );
		$this->assertArrayHasKey( 'store_manual_flagged', $by_code );
		$this->assertTrue( $by_code['store_manual_flagged'], 'an ordinary coupon is removable' );
	}
}
