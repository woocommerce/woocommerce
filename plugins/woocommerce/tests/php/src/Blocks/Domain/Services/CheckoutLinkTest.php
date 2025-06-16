<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutLink;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CheckoutLink.
 */
class CheckoutLinkTest extends TestCase {
	/**
	 * Setup the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$_GET = [];
		// Reset global cart/session if needed.
		$GLOBALS['added_to_cart']   = [];
		$GLOBALS['applied_coupons'] = [];
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		$_GET                       = [];
		$GLOBALS['added_to_cart']   = [];
		$GLOBALS['applied_coupons'] = [];
		parent::tearDown();
	}

	/**
	 * Test that products and coupon are added and token in url.
	 */
	public function test_products_and_coupon_are_added_and_token_in_url() {
		$test_products = [
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
		];

		$product_ids = array_map(
			function ( $product ) {
				return $product->get_id();
			},
			$test_products
		);

		$coupon = CouponHelper::create_coupon( 'test-coupon' );

		$_GET['products'] = implode( ',', $product_ids );
		$_GET['coupon']   = 'test-coupon';

		$service = new class() extends CheckoutLink {
			/**
			 * Get the checkout link for testing.
			 *
			 * @return string The checkout link.
			 */
			public function get_checkout_link_test() {
				return parent::get_checkout_link();
			}
		};

		$url = $service->get_checkout_link_test();

		$cart_contents    = WC()->cart->get_cart();
		$cart_product_ids = array_map(
			function ( $item ) {
				return $item['product_id'];
			},
			$cart_contents
		);

		$applied_coupons      = WC()->cart->get_coupons();
		$applied_coupon_codes = array_map(
			function ( $coupon ) {
				return $coupon->get_code();
			},
			$applied_coupons
		);

		$this->assertEquals( array_values( $product_ids ), array_values( $cart_product_ids ) );
		$this->assertEquals( array_values( [ 'test-coupon' ] ), array_values( $applied_coupon_codes ) );
		$this->assertStringContainsString( 'session=', $url );

		// Clean up.
		foreach ( $test_products as $product ) {
			wp_delete_post( $product->get_id(), true );
		}
	}
}
