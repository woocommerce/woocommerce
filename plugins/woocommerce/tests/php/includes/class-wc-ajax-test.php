<?php
/**
 * Class WC_AJAX_Test file.
 *
 * @package WooCommerce\Tests\WC_AJAX.
 */

use Automattic\WooCommerce\Internal\Orders\CouponsController;
use Automattic\WooCommerce\Internal\Orders\TaxesController;

/**
 * Class WC_AJAX_Test file.
 */
class WC_AJAX_Test extends \WP_Ajax_UnitTestCase {

	/**
	 * Stock should not be reduced from AJAX when an item is added to an order.
	 */
	public function test_add_item_to_pending_payment_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		// Stock should not have been reduced because order status is 'pending'.
		$this->assertEquals( 1000, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( false, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Stock should be reduced from AJAX when an item is added to an order, when status is being changed
	 */
	public function test_add_item_to_processing_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );
		$order->set_status( 'processing' );
		$order->save();

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 990, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( 10, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Creating an API Key with too long of a description should report failure.
	 */
	public function test_create_api_key_long_description_failure() {
		$this->skip_on_php_8_1();

		$this->_setRole( 'administrator' );

		$description  = 'This_description_is_really_very_long_and_is_meant_to_exceed_the_database_column_length_of_200_characters_';
		$description .= $description;

		$_POST['security']    = wp_create_nonce( 'update-api-key' );
		$_POST['key_id']      = 0;
		$_POST['user']        = 1;
		$_POST['permissions'] = 'read';
		$_POST['description'] = $description;

		try {
			$this->_handleAjax( 'woocommerce_update_api_key' );
		} catch ( WPAjaxDieContinueException $e ) {
			// wp_die() doesn't actually occur, so we need to clean up WC_AJAX::update_api_key's output buffer.
			ob_end_clean();
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );
		$this->assertEquals( $response['data']['message'], 'There was an error generating your API Key.' );
	}

	/**
	 * Skip the current test on PHP 8.1 and higher.
	 * TODO: Remove this method and its usages once WordPress is compatible with PHP 8.1. Please note that there are multiple copies of this method.
	 */
	protected function skip_on_php_8_1() {
		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			$this->markTestSkipped( 'Waiting for WordPress compatibility with PHP 8.1' );
		}
	}

	/**
	 * Test coupon and recalculation of totals sequences when product prices are tax inclusive.
	 */
	public function test_apply_coupon_with_tax_inclusive_settings() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'IN:AP' );

		$tax_rate = array(
			'tax_rate_country' => 'IN',
			'tax_rate_state'   => '',
			'tax_rate'         => '20',
			'tax_rate_name'    => 'tax',
			'tax_rate_order'   => '1',
			'tax_rate_class'   => '',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 120 );
		$product->save();

		$coupon = new WC_Coupon();
		$coupon->set_code( '10off' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );

		$container          = wc_get_container();
		$coupons_controller = $container->get( CouponsController::class );
		$taxes_controller   = $container->get( TaxesController::class );

		$item        = current( $order->get_items() );
		$item_id     = $item->get_id();
		$items_array = array(
			'order_item_id'  => array( $item_id ),
			'order_item_qty' => array( $item_id => $item->get_quantity() ),
			'line_subtotal'  => array( $item_id => $item->get_subtotal() ),
			'line_total'     => array( $item_id => $item->get_total() ),
		);

		$calc_taxes_post_variables = array(
			'order_id' => $order->get_id(),
			'items'    => http_build_query( $items_array ),
			'country'  => $tax_rate['tax_rate_country'],
			'state'    => $tax_rate['tax_rate_state'],
		);

		$add_coupon_post_variables = array(
			'order_id' => $order->get_id(),
			'coupon'   => $coupon->get_code(),
		);

		$taxes_controller->calc_line_taxes( $calc_taxes_post_variables );
		$coupons_controller->add_coupon_discount( $add_coupon_post_variables );

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 108, $order->get_total() );
	}
}
