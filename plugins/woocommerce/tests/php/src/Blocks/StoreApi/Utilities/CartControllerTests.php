<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit tests for the CartController class.
 */
class CartControllerTests extends TestCase {
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

		$out_of_stock_in_cart->set_stock_status( 'outofstock' );
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
