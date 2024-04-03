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
		$coupon      = WC_Helper_Coupon::create_coupon( $coupon_code );
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

	/**
	 * Test apply_coupon() stores coupon meta data.
	 * See: https://github.com/woocommerce/woocommerce/issues/28166.
	 */
	public function test_apply_coupon_stores_meta_data() {
		$coupon_code = 'coupon_test_meta_data';
		$coupon      = WC_Helper_Coupon::create_coupon( $coupon_code );
		$order       = WC_Helper_Order::create_order();
		$order->set_status( 'processing' );
		$order->save();
		$order->apply_coupon( $coupon_code );

		$coupon_items = $order->get_items( 'coupon' );
		$this->assertCount( 1, $coupon_items );

		$coupon_info = ( current( $coupon_items ) )->get_meta( 'coupon_info' );
		$this->assertNotEmpty( $coupon_info, 'WC_Order_Item_Coupon missing `coupon_info` meta.' );
		$coupon_info = json_decode( $coupon_info, true );
		$this->assertEquals( $coupon->get_id(), $coupon_info[0] );
		$this->assertEquals( $coupon_code, $coupon_info[1] );
	}

	/**
	 * Test for get_discount_to_display which must return a value
	 * with and without tax whatever the setting of the options.
	 *
	 * Issue :https://github.com/woocommerce/woocommerce/issues/36794
	 */
	public function test_get_discount_to_display() {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		// Set dummy data.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'tax',
			'tax_rate_priority' => '1',
			'tax_rate_order'    => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$coupon  = WC_Helper_Coupon::create_coupon();
		$product = WC_Helper_Product::create_simple_product( true, array( 'price' => 10 ) );

		$order = new WC_Order();
		$order->add_product( $product );
		$order->apply_coupon( $coupon );
		$order->calculate_totals();
		$order->save();

		$this->assertEquals( wc_price( 1, array( 'currency' => 'USD' ) ), $order->get_discount_to_display( 'excl' ) );
		$this->assertEquals( wc_price( 1.20, array( 'currency' => 'USD' ) ), $order->get_discount_to_display( 'incl' ) );
	}

	/**
	 * @testDox Cache does not interfere if wc_get_order returns a different class than WC_Order.
	 */
	public function test_cache_does_not_interferes_with_order_object() {
		add_action(
			'woocommerce_new_order',
			function( $order_id ) {
				// this makes the cache store a specific order class instance, but it's quickly replaced by a generic one
				// as we're in the middle of a save and this gets executed before the logic in WC_Abstract_Order.
				$order = wc_get_order( $order_id );
			}
		);
		$order = new WC_Order();
		$order->save();

		$order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( Automattic\WooCommerce\Admin\Overrides\Order::class, $order );
	}

	/**
	 * @testDox When a taxonomy with a default term is set on the order, it's inserted when a new order is created.
	 */
	public function test_default_term_for_custom_taxonomy() {
		$custom_taxonomy = register_taxonomy(
			'custom_taxonomy',
			'shop_order',
			array(
				'default_term' => 'new_term',
			),
		);

		// Set user who has access to create term.
		$current_user_id = get_current_user_id();
		$user            = new WP_User( wp_create_user( 'test', '' ) );
		$user->set_role( 'administrator' );
		wp_set_current_user( $user->ID );

		$order = wc_create_order();

		wp_set_current_user( $current_user_id );
		$order_terms = wp_list_pluck( wp_get_object_terms( $order->get_id(), $custom_taxonomy->name ), 'name' );
		$this->assertContains( 'new_term', $order_terms );
	}

	/**
	 * @testDox Test that order items are not mixed when order_id is zero.
	 */
	public function test_order_items_shouldnot_mix_with_zero_id() {
		$order1 = new WC_Order();
		$order2 = new WC_Order();

		$product1_for_order1 = WC_Helper_Product::create_simple_product();
		$product2_for_order1 = WC_Helper_Product::create_simple_product();
		$product_for_order2 = WC_Helper_Product::create_simple_product();

		$item1_1 = new WC_Order_Item_Product();
		$item1_1->set_product( $product1_for_order1 );
		$item1_1->set_quantity( 1 );
		$item1_1->save();

		$item1_2 = new WC_Order_Item_Product();
		$item1_2->set_product( $product2_for_order1 );
		$item1_2->set_quantity( 1 );
		$item1_2->save();

		$item2 = new WC_Order_Item_Product();
		$item2->set_product( $product_for_order2 );
		$item2->set_quantity( 1 );
		$item2->save();

		$order1->add_item( $item1_1 );
		$order2->add_item( $item2 );
		$order1->add_item( $item1_2 );

		$this->assertCount( 1, $order2->get_items( 'line_item' ) );
		$this->assertCount( 2, $order1->get_items( 'line_item' ) );

		$this->assertEquals( $item1_2->get_id(), $order1->get_items( 'line_item' )[1]->get_id() );
		$this->assertEquals( $item1_1->get_id(), $order1->get_items( 'line_item' )[0]->get_id() );

		$this->assertEquals( $item2->get_id(), $order2->get_items( 'line_item' )[0]->get_id() );
	}
}
