<?php
/**
 * Class WC_Abstract_Order file.
 *
 * @package WooCommerce\Tests\Abstracts
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Class WC_Abstract_Order.
 */
class WC_Abstract_Order_Test extends WC_Unit_Test_Case {

	/**
	 * Test when rounding is different when doing per line and in subtotal.
	 */
	public function test_order_calculate_26582() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '15.0000',
			'tax_rate_name'     => 'tax',
			'tax_rate_priority' => '1',
			'tax_rate_order'    => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 99.48 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 108.68 );
		$product2->save();

		$order = new WC_Order();
		$order->add_product( $product1, 6 );
		$order->add_product( $product2, 6 );
		$order->save();

		$this->order_calculate_rounding_line( $order );
		$this->order_calculate_rounding_subtotal( $order );
	}

	/**
	 * Helper method to test rounding per line for `test_order_calculate_26582`.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function order_calculate_rounding_line( $order ) {
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		$order->calculate_totals( true );

		$this->assertEquals( 1086.06, $order->get_subtotal() );
		$this->assertEquals( 162.90, $order->get_total_tax() );
		$this->assertEquals( 1248.96, $order->get_total() );
	}

	/**
	 * Helper method to test rounding at subtotal for `test_order_calculate_26582`.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function order_calculate_rounding_subtotal( $order ) {
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		$order->calculate_totals( true );

		$this->assertEquals( 1086.05, $order->get_subtotal() );
		$this->assertEquals( 162.91, $order->get_total_tax() );
		$this->assertEquals( 1248.96, $order->get_total() );
	}

	/**
	 * Test that coupon taxes are not affected by logged in admin user.
	 */
	public function test_apply_coupon_for_correct_location_taxes() {
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$password = wp_generate_password( 8, false, false );
		$admin_id = wp_insert_user(
			array(
				'user_login' => "test_admin$password",
				'user_pass'  => $password,
				'user_email' => "admin$password@example.com",
				'role'       => 'administrator',
			)
		);

		update_user_meta( $admin_id, 'billing_country', 'MV' ); // Different than customer's address and base location.
		wp_set_current_user( $admin_id );
		WC()->customer = null;
		WC()->initialize_cart();

		update_option( 'woocommerce_default_country', 'IN:AP' );

		$tax_rate = array(
			'tax_rate_country' => 'IN',
			'tax_rate_state'   => '',
			'tax_rate'         => '25.0000',
			'tax_rate_name'    => 'tax',
			'tax_rate_order'   => '1',
			'tax_rate_class'   => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->set_billing_country( 'IN' );
		$order->add_product( $product, 1 );
		$order->save();
		$order->calculate_totals();

		$this->assertEquals( 100, $order->get_total() );
		$this->assertEquals( 80, $order->get_subtotal() );
		$this->assertEquals( 20, $order->get_total_tax() );

		$coupon = new WC_Coupon();
		$coupon->set_code( '10off' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$order->apply_coupon( '10off' );

		$this->assertEquals( 8, $order->get_discount_total() );
		$this->assertEquals( 90, $order->get_total() );
		$this->assertEquals( 18, $order->get_total_tax() );
		$this->assertEquals( 2, $order->get_discount_tax() );
	}

	/**
	 * @testdox 'add_product' passes the order supplied in '$args' to 'wc_get_price_excluding_tax', and uses the obtained price as total and subtotal for the line item.
	 */
	public function test_add_product_passes_order_to_wc_get_price_excluding_tax() {
		$product_passed_to_get_price = false;
		$args_passed_to_get_price    = false;

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_get_price_excluding_tax' => function( $product, $args = array() ) use ( &$product_passed_to_get_price, &$args_passed_to_get_price ) {
						$product_passed_to_get_price = $product;
						$args_passed_to_get_price    = $args;

						return 1234;
				},
			)
		);

		//phpcs:disable Squiz.Commenting
		$order_item = new class() extends WC_Order_Item_Product {
			public $passed_props;

			public function set_props( $args, $context = 'set' ) {
				$this->passed_props = $args;
			}
		};
		//phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_class_mocks(
			array( 'WC_Order_Item_Product' => $order_item )
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();

		$order->add_product( $product, 1, array( 'order' => $order ) );

		$this->assertSame( $product, $product_passed_to_get_price );
		$this->assertSame( $order, $args_passed_to_get_price['order'] );
		$this->assertEquals( 1234, $order_item->passed_props['total'] );
		$this->assertEquals( 1234, $order_item->passed_props['subtotal'] );
	}

	/**
	 * Test get coupon usage count across statuses.
	 */
	public function test_apply_coupon_across_status() {
		$coupon_code = 'coupon_test_count_across_status';
		$coupon = WC_Helper_Coupon::create_coupon( $coupon_code );
		$this->assertEquals( 0, $coupon->get_usage_count() );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$order->apply_coupon( $coupon_code );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Change order status to anything other than cancelled should not change coupon count.
		$order->set_status( 'processing' );
		$order->save();
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Cancelling order should reduce coupon count.
		$order->set_status( 'cancelled' );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );
	}

	/**
	 * Test get multiple coupon usage count across statuses.
	 */
	public function test_apply_coupon_multiple_across_status() {
		$coupon_code_1 = 'coupon_test_count_across_status_1';
		$coupon_code_2 = 'coupon_test_count_across_status_2';
		$coupon_code_3 = 'coupon_test_count_across_status_3';
		WC_Helper_Coupon::create_coupon( $coupon_code_1 );
		WC_Helper_Coupon::create_coupon( $coupon_code_2 );
		WC_Helper_Coupon::create_coupon( $coupon_code_3 );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();
		$order->apply_coupon( $coupon_code_1 );
		$order->apply_coupon( $coupon_code_2 );
		$order->apply_coupon( $coupon_code_3 );

		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Change order status to anything other than cancelled should not change coupon count.
		$order->set_status( 'processing' );
		$order->save();
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Cancelling order should reduce coupon count.
		$order->set_status( 'cancelled' );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );
	}
}
