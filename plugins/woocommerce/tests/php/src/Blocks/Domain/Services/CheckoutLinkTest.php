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

		// Clear WooCommerce cart.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		$_GET                       = [];
		$GLOBALS['added_to_cart']   = [];
		$GLOBALS['applied_coupons'] = [];

		// Clear WooCommerce cart.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

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

	/**
	 * Test that products can be added using SKUs.
	 */
	public function test_products_can_be_added_using_skus() {
		$test_products = [
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
		];

		// Set SKUs for test products.
		$test_products[0]->set_sku( 'TEST-SKU-001' );
		$test_products[0]->save();
		$test_products[1]->set_sku( 'TEST-SKU-002' );
		$test_products[1]->save();

		$product_skus = [
			$test_products[0]->get_sku(),
			$test_products[1]->get_sku(),
		];
		$product_ids  = [
			$test_products[0]->get_id(),
			$test_products[1]->get_id(),
		];

		$_GET['products'] = implode( ',', $product_skus );

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

		$this->assertEquals( array_values( $product_ids ), array_values( $cart_product_ids ) );

		// Clean up.
		foreach ( $test_products as $product ) {
			wp_delete_post( $product->get_id(), true );
		}
	}

	/**
	 * Test mixed ID and SKU usage.
	 */
	public function test_mixed_id_and_sku_usage() {
		$test_products = [
			\WC_Helper_Product::create_simple_product(),
			\WC_Helper_Product::create_simple_product(),
		];

		// Set SKU for first product, use ID for second.
		$test_products[0]->set_sku( 'MIXED-TEST-SKU' );
		$test_products[0]->save();

		$product_identifiers = [
			$test_products[0]->get_sku(),  // Use SKU.
			$test_products[1]->get_id(),   // Use ID.
		];
		$expected_ids        = [
			$test_products[0]->get_id(),
			$test_products[1]->get_id(),
		];

		$_GET['products'] = implode( ',', $product_identifiers );

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

		$this->assertEquals( array_values( $expected_ids ), array_values( $cart_product_ids ) );

		// Clean up.
		foreach ( $test_products as $product ) {
			wp_delete_post( $product->get_id(), true );
		}
	}

	/**
	 * Test that invalid SKUs and IDs are skipped.
	 */
	public function test_invalid_skus_and_ids_are_skipped() {
		$test_product = \WC_Helper_Product::create_simple_product();
		$test_product->set_sku( 'VALID-SKU' );
		$test_product->save();

		// Mix valid and invalid identifiers.
		$_GET['products'] = 'VALID-SKU,INVALID-SKU,99999,';

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

		// Only the valid SKU should be added.
		$this->assertEquals( [ $test_product->get_id() ], array_values( $cart_product_ids ) );

		// Clean up.
		wp_delete_post( $test_product->get_id(), true );
	}
}
