<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Unit tests for the CartController class.
 */
class CartControllerTests extends TestCase {
	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

	/**
	 * Test the normalize_cart method.
	 */
	public function test_normalize_cart() {
		$class    = new CartController();
		$fixtures = new FixtureData();

		$product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);

		// Test maximum quantity after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 5 );
		add_filter(
			'woocommerce_store_api_product_quantity_maximum',
			function () {
				return 2;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 2, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_maximum' );
		wc()->cart->empty_cart();

		// Test minimum quantity after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 1 );
		add_filter(
			'woocommerce_store_api_product_quantity_minimum',
			function () {
				return 5;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 5, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_minimum' );
		wc()->cart->empty_cart();

		// Test multiple of after normalizing.
		$product_key = wc()->cart->add_to_cart( $product->get_id(), 7 );
		add_filter(
			'woocommerce_store_api_product_quantity_multiple_of',
			function () {
				return 3;
			},
			10
		);
		$class->normalize_cart();
		$this->assertEquals( 6, wc()->cart->get_cart_item( $product_key )['quantity'] );
		remove_all_filters( 'woocommerce_store_api_product_quantity_multiple_of' );
		wc()->cart->empty_cart();
	}

	/**
	 * Test cart error code is getting exposed.
	 */
	public function test_get_cart_errors() {
		$class    = new CartController();
		$fixtures = new FixtureData();

		// This product will simply be in/out of stock.
		$out_of_stock_product     = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'regular_price' => 10,
			)
		);
		$out_of_stock_product_key = wc()->cart->add_to_cart( $out_of_stock_product->get_id(), 2 );
		$out_of_stock_in_cart     = wc()->cart->get_cart_item( $out_of_stock_product_key )['data'];

		// This product will have exact levels of stock known.
		$partially_out_of_stock_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 2',
				'regular_price' => 10,
			)
		);
		$partially_out_of_stock_key     = wc()->cart->add_to_cart( $partially_out_of_stock_product->get_id(), 4 );
		$partially_out_of_stock_in_cart = wc()->cart->get_cart_item( $partially_out_of_stock_key )['data'];

		// This product will have exact levels of stock known.
		$too_many_in_cart_product     = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 3',
				'regular_price' => 10,
			)
		);
		$too_many_in_cart_product_key = wc()->cart->add_to_cart( $too_many_in_cart_product->get_id(), 4 );
		$too_many_in_cart_in_cart     = wc()->cart->get_cart_item( $too_many_in_cart_product_key )['data'];

		$out_of_stock_in_cart->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$partially_out_of_stock_in_cart->set_manage_stock( true );
		$partially_out_of_stock_in_cart->set_stock_quantity( 2 );
		$too_many_in_cart_in_cart->set_sold_individually( true );

		// This product will not be purchasable.
		$not_purchasable_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 4',
				'regular_price' => 10,
			)
		);
		wc()->cart->add_to_cart( $not_purchasable_product->get_id(), 2 );

		// This function will force the $product->is_purchasable() function to return false for our $not_purchasable_product.
		add_filter(
			'woocommerce_is_purchasable',
			function ( $is_purchasable, $product ) use ( $not_purchasable_product ) {
				if ( $product->get_id() === $not_purchasable_product->get_id() ) {
					return false;
				}
				return true;
			},
			10,
			2
		);

		$errors = $class->get_cart_errors();

		$this->assertTrue( is_wp_error( $errors ) );
		$this->assertTrue( $errors->has_errors() );

		$error_codes     = $errors->get_error_codes();
		$expected_errors = array(
			'woocommerce_rest_product_partially_out_of_stock',
			'woocommerce_rest_product_out_of_stock',
			'woocommerce_rest_product_not_purchasable',
			'woocommerce_rest_product_too_many_in_cart',
		);

		foreach ( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $error_codes );
		}
	}
}
