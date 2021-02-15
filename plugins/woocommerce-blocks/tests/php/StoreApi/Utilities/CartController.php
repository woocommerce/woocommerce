<?php
/**
 * OrderController Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use PHPUnit\Framework\TestCase;
use \WC_Helper_Product as ProductHelper;

class OrderControllerTests extends TestCase {

	public function test_get_cart_item_errors()    {
		$class = new CartController();

		// This product will simply be in/out of stock.
		$out_of_stock_product = ProductHelper::create_simple_product();
		$out_of_stock_product_key = wc()->cart->add_to_cart( $out_of_stock_product->get_id(), 2 );
		$out_of_stock_in_cart = wc()->cart->get_cart_item( $out_of_stock_product_key )['data'];

		// This product will have exact levels of stock known
		$partially_out_of_stock_product = ProductHelper::create_simple_product();
		$partially_out_of_stock_key = wc()->cart->add_to_cart( $partially_out_of_stock_product->get_id(), 4 );
		$partially_out_of_stock_in_cart = wc()->cart->get_cart_item( $partially_out_of_stock_key )['data'];

		// This product will have exact levels of stock known
		$too_many_in_cart_product = ProductHelper::create_simple_product();
		$too_many_in_cart_product_key = wc()->cart->add_to_cart( $too_many_in_cart_product->get_id(), 4 );
		$too_many_in_cart_in_cart = wc()->cart->get_cart_item( $too_many_in_cart_product_key )['data'];

		$out_of_stock_in_cart->set_stock_status( 'outofstock' );
		$partially_out_of_stock_in_cart->set_manage_stock( true );
		$partially_out_of_stock_in_cart->set_stock_quantity( 2 );
		$too_many_in_cart_in_cart->set_sold_individually( true );

		// This product will not be purchasable
		$not_purchasable_product = ProductHelper::create_simple_product();
		wc()->cart->add_to_cart( $not_purchasable_product->get_id(), 2 );

		// This function will force the $product->is_purchasable() function to return false for our $not_purchasable_product
		add_filter( 'woocommerce_is_purchasable', function( $is_purchasable, $product ) use ( $not_purchasable_product ) {
			if ( $product->get_id() === $not_purchasable_product->get_id() ) {
				return false;
			}
			return true;
		}, 10, 2 );

		$errors = array_map(
			function( $error ) {
				return $error->get_error_code();
			},
			$class->get_cart_item_errors()
		);

		$expected_errors = [
			'woocommerce-blocks-product-partially-out-of-stock',
			'woocommerce-blocks-product-out-of-stock',
			'woocommerce-blocks-product-not-purchasable',
			'woocommerce-blocks-too-many-of-product-in-cart',
		];

		foreach( $expected_errors as $expected_error ) {
			$this->assertContains( $expected_error, $errors );
		}

	}

}
