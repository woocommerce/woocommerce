<?php
/**
 * Class WC_Abstract_Order file.
 *
 * @package WooCommerce\Tests\Abstracts
 */

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Backward compatibility.
/**
 * Class WC_Abstract_Order.
 */
class WC_Abstract_Order_Test extends WC_Unit_Test_Case {

	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Post statuses registered during a test, to clean up on tear down.
	 *
	 * @var string[]
	 */
	private $registered_order_statuses = array();

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		// register_post_status() mutates the global registry, which WP_UnitTestCase does not restore.
		foreach ( $this->registered_order_statuses as $status ) {
			unset( $GLOBALS['wp_post_statuses'][ $status ] );
		}
		$this->registered_order_statuses = array();

		parent::tearDown();
		$this->disable_cogs_feature();
	}

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
				'wc_get_price_excluding_tax' => function ( $product, $args = array() ) use ( &$product_passed_to_get_price, &$args_passed_to_get_price ) {
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
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->apply_coupon( $coupon_code );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Change order status to anything other than cancelled should not change coupon count.
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Cancelling order should reduce coupon count.
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Failed order should reduce coupon count.
		$order->set_status( OrderStatus::FAILED );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code ) )->get_usage_count() );

		// Trashed order should reduce coupon count.
		$order->delete();
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
		$order->set_status( OrderStatus::PENDING );
		$order->save();
		$order->apply_coupon( $coupon_code_1 );
		$order->apply_coupon( $coupon_code_2 );
		$order->apply_coupon( $coupon_code_3 );

		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Change order status to anything other than cancelled should not change coupon count.
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Cancelling order should reduce coupon count.
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Failed order should reduce coupon count.
		$order->set_status( OrderStatus::FAILED );
		$order->save();
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_1 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_2 ) )->get_usage_count() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon_code_3 ) )->get_usage_count() );

		// Trashed order should reduce coupon count.
		$order->delete();
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
		$order->set_status( OrderStatus::PROCESSING );
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
			function ( $order_id ) {
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
		$product_for_order2  = WC_Helper_Product::create_simple_product();

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

		$order1_items = array_keys( $order1->get_items( 'line_item' ) );

		$this->assertContains( $item1_1->get_id(), $order1_items );
		$this->assertContains( $item1_1->get_id(), $order1_items );

		$this->assertEquals( $item2->get_id(), array_keys( $order2->get_items( 'line_item' ) )[0] );
	}

	/**
	 * @testdox Abstract order classes don't manage Cost of Goods Sold by default.
	 */
	public function test_abstract_orders_dont_have_cogs_by_default() {
		$order = new class() extends WC_Abstract_Order {
		};

		$this->assertFalse( $order->has_cogs() );
	}

	/**
	 * @testdox The regular order class manages a Cost of Goods Sold value.
	 */
	public function test_orders_have_cogs() {
		$order = new WC_Order();

		$this->assertTrue( $order->has_cogs() );
	}

	/**
	 * @testdox 'calculate_cogs_total_value' returns zero, and 'doing it wrong' is thrown, if the Cost of Goods Sold feature is disabled.
	 */
	public function test_calculate_total_cogs_simply_returns_false_if_cogs_disabled() {
		$order = new WC_Order();

		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Abstract_Order::calculate_cogs_total_value' );
		$this->assertEquals( 0, $order->calculate_cogs_total_value() );
	}

	/**
	 * @testdox 'calculate_cogs_total_value' returns false if the Cost of Goods Sold feature is enabled but the class doesn't manage it.
	 */
	public function test_calculate_cogs_simply_returns_false_if_cogs_enabled_but_class_has_no_cogs() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$order = new class() extends WC_Order {
			public function has_cogs(): bool {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting
		$this->add_product_with_cogs_to_order( $order, 12.34, 1 );

		$this->assertEquals( 0, $order->calculate_cogs_total_value() );
	}

	/**
	 * @testdox 'calculate_cogs_total_value' calculates the value from the prices and the quantities of all the items with a Cost of Goods Sold value.
	 */
	public function test_calculate_cogs_uses_product_info_and_sets_the_value() {
		$this->enable_cogs_feature();

		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, 12.34, 2 );
		$this->add_product_with_cogs_to_order( $order, 56.78, 3 );

		$fee = new WC_Order_Item_Fee(); // Example of line item without COGS.
		$order->add_item( $fee );

		$calculated_value = $order->calculate_cogs_total_value();
		$this->assertEquals( 12.34 * 2 + 56.78 * 3, $calculated_value );
		$this->assertEquals( $calculated_value, $order->get_cogs_total_value() );
	}

	/**
	 * @testdox The 'calculate_cogs_total_value_core' method can be overridden in derived classes.
	 */
	public function test_calculate_cogs_core_can_be_overridden() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$order = new class() extends WC_Order {
			protected function calculate_cogs_total_value_core(): float {
				return 999.34;
			}
		};
		// phpcs:enable Squiz.Commenting
		$this->add_product_with_cogs_to_order( $order, 12.34, 2 );

		$calculated_value = $order->calculate_cogs_total_value();
		$this->assertEquals( 999.34, $calculated_value );
		$this->assertEquals( $calculated_value, $order->get_cogs_total_value() );
	}

	/**
	 * @testdox The calculated value for Cost of Goods Sold can be modified using the 'woocommerce_calculated_order_cogs_value' filter.
	 */
	public function test_filter_can_be_used_to_alter_calculated_cogs_value() {
		$filter_received_value = null;
		$filter_received_order = null;

		$this->enable_cogs_feature();

		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, 12.34, 2 );
		$this->add_product_with_cogs_to_order( $order, 56.78, 3 );

		add_filter(
			'woocommerce_calculated_order_cogs_value',
			function ( $value, $order ) use ( &$filter_received_value, &$filter_received_order ) {
				$filter_received_value = $value;
				$filter_received_order = $order;
				return 999.34;
			},
			10,
			2
		);

		$calculate_method_result = $order->calculate_cogs_total_value();

		$this->assertEquals( 12.34 * 2 + 56.78 * 3, $filter_received_value );
		$this->assertEquals( 999.34, $calculate_method_result );
		$this->assertEquals( $calculate_method_result, $order->get_cogs_total_value() );
		$this->assertSame( $order, $filter_received_order );
	}

	/**
	 * Add a product order item with a given Cost of Goods Sold to an exising order.
	 *
	 * @param WC_Order $order The target order.
	 * @param float    $cogs_value The COGS value of the product.
	 * @param int      $quantity The quantity of the order item.
	 */
	private function add_product_with_cogs_to_order( WC_Order $order, float $cogs_value, int $quantity ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_cogs_value( $cogs_value );
		$product->save();
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( $quantity );
		$item->save();
		$order->add_item( $item );
	}

	/**
	 * @testdox get_cogs_total_value_html throws 'doing it wrong' if the Cost of Goods Sold feature is disabled.
	 */
	public function test_get_cogs_total_value_html_with_cogs_disabled() {
		$order = new WC_Order();

		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Abstract_Order::get_cogs_total_value_html' );

		$order->get_cogs_total_value_html();
	}

	/**
	 * @testdox Test get_cogs_total_value_html with implicit WC_Price argument value.
	 */
	public function test_get_cogs_total_value_html_with_implicit_arguments() {
		$this->enable_cogs_feature();

		$order = $this->get_order_with_fixed_cogs_total_value();

		$actual   = $order->get_cogs_total_value_html();
		$expected = wc_price( 12.34, array( 'currency' => $order->get_currency() ) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox Test get_cogs_total_value_html with explicit WC_Price argument value.
	 */
	public function test_get_cogs_total_value_html_with_explicit_arguments() {
		$this->enable_cogs_feature();

		$order = $this->get_order_with_fixed_cogs_total_value();

		$actual   = $order->get_cogs_total_value_html( array( 'currency' => '!' ) );
		$expected = wc_price( 12.34, array( 'currency' => '!' ) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox Test the woocommerce_order_cogs_total_value_html filter invoked by get_cogs_total_value_html.
	 */
	public function test_get_cogs_total_value_html_with_filter() {
		$this->enable_cogs_feature();

		$order = $this->get_order_with_fixed_cogs_total_value();

		add_filter(
			'woocommerce_order_cogs_total_value_html',
			function ( $html, $amount, $the_order ) {
				return sprintf( 'amount: %s, order: %s', $amount, $the_order->get_id() );
			},
			10,
			3
		);

		$actual = $order->get_cogs_total_value_html();
		remove_all_filters( 'woocommerce_order_cogs_total_value_html' );
		$expected = sprintf( 'amount: %s, order: %s', 12.34, $order->get_id() );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox update_taxes persists cart and shipping tax totals as order tax items, and updates existing items in-place on a second call.
	 */
	public function test_update_taxes_persists_cart_and_shipping_tax_totals(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// German standard 19% non-compound VAT rate.
		$tax_rate    = array(
			'tax_rate_country'  => 'DE',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		$product = WC_Helper_Product::create_simple_product();
		$order   = new WC_Order();

		// Line item carrying $1.00 cart tax for the rate.
		// WC_Order_Item_Product::set_taxes() requires both 'total' and 'subtotal' to be non-empty.
		$line_item = new WC_Order_Item_Product();
		$line_item->set_product( $product );
		$line_item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => '1.00' ),
				'subtotal' => array( $tax_rate_id => '1.00' ),
			)
		);
		$order->add_item( $line_item );

		// Shipping item carrying $0.50 shipping tax for the rate.
		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_taxes( array( 'total' => array( $tax_rate_id => '0.50' ) ) );
		$order->add_item( $shipping_item );

		$order->save();
		$order->update_taxes();

		$tax_items = $order->get_taxes();
		$this->assertCount( 1, $tax_items );

		/** @var WC_Order_Item_Tax $tax_item */
		$tax_item = reset( $tax_items );
		// Confirm the German 19% VAT rate is correctly associated with the tax item.
		$this->assertSame( 19.0, (float) $tax_item->get_rate_percent() );
		// Cart and shipping taxes are accumulated from line items and persisted on the tax item.
		$this->assertSame( 1.00, (float) $tax_item->get_tax_total() );
		$this->assertSame( 0.50, (float) $tax_item->get_shipping_tax_total() );
		// Order-level totals are rolled up from all tax items.
		$this->assertSame( 1.00, (float) $order->get_cart_tax() );
		$this->assertSame( 0.50, (float) $order->get_shipping_tax() );

		// Second call: update line item taxes and verify existing tax item is updated in-place, not duplicated.
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$item->set_taxes(
					array(
						'total'    => array( $tax_rate_id => '2.00' ),
						'subtotal' => array( $tax_rate_id => '2.00' ),
					)
				);
				$item->save();
			}
		}

		$order->update_taxes();

		$tax_items_after = $order->get_taxes();
		$this->assertCount( 1, $tax_items_after, 'update_taxes() must update the existing tax item, not create a duplicate.' );

		$tax_item_after = reset( $tax_items_after );
		$this->assertSame( 2.00, (float) $tax_item_after->get_tax_total() );
		$this->assertSame( 0.50, (float) $tax_item_after->get_shipping_tax_total() );
	}

	/**
	 * @testdox calculate_taxes handles inherited shipping tax when no taxable product class is available.
	 * @dataProvider inherited_shipping_tax_without_taxable_products_provider
	 *
	 * @param bool  $add_non_taxable_product Whether to add a non-taxable product to the order.
	 * @param bool  $add_non_taxable_fee     Whether to add a non-taxable fee to the order.
	 * @param float $expected_shipping_tax   Expected shipping tax total.
	 */
	public function test_calculate_taxes_handles_inherited_shipping_tax_without_taxable_products( bool $add_non_taxable_product, bool $add_non_taxable_fee, float $expected_shipping_tax ): void {
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes', 'no' );
		$original_shipping_tax_class = get_option( 'woocommerce_shipping_tax_class', 'inherit' );
		$order                       = new WC_Order();
		$product                     = null;

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		$tax_rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Standard tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		try {
			if ( $add_non_taxable_product ) {
				$product = WC_Helper_Product::create_simple_product();
				$product->set_tax_status( ProductTaxStatus::NONE );
				$product->save();
				$order->add_product( $product );
			}

			if ( $add_non_taxable_fee ) {
				$fee_item = new WC_Order_Item_Fee();
				$fee_item->set_name( 'Manual fee' );
				$fee_item->set_amount( '5' );
				$fee_item->set_total( '5' );
				$fee_item->set_tax_status( ProductTaxStatus::NONE );
				$order->add_item( $fee_item );
			}

			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_title( 'Manual shipping' );
			$shipping_item->set_total( '10' );
			$order->add_item( $shipping_item );

			$order->calculate_totals();

			$this->assertSame( $expected_shipping_tax, (float) $order->get_shipping_tax() );
		} finally {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
			update_option( 'woocommerce_shipping_tax_class', $original_shipping_tax_class );
			$order->delete( true );
			if ( $product ) {
				$product->delete( true );
			}
		}
	}

	/**
	 * Data provider for inherited shipping tax calculations without taxable products.
	 *
	 * @return array<string, array{bool, bool, float}>
	 */
	public function inherited_shipping_tax_without_taxable_products_provider(): array {
		return array(
			'shipping-only order'           => array( false, false, 1.0 ),
			'non-taxable product'           => array( true, false, 0.0 ),
			'non-taxable fee with shipping' => array( false, true, 1.0 ),
		);
	}

	/**
	 * Get an order object with a fixed total COGS value.
	 *
	 * @return WC_Order
	 */
	private function get_order_with_fixed_cogs_total_value(): WC_Order {
		// phpcs:disable Squiz.Commenting
		return new class() extends WC_Order {
			public function get_cogs_total_value(): float {
				return 12.34;
			}
		};
		// phpcs:enable Squiz.Commenting
	}

	/**
	 * @testdox Should defer bulk item deletion until save() is called.
	 */
	public function test_remove_order_items_defers_db_deletion_until_save() {
		$order    = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->assertGreaterThan( 0, count( wc_get_order( $order_id )->get_items() ), 'Precondition: order should have line items in the DB.' );
		$this->assertGreaterThan( 0, count( wc_get_order( $order_id )->get_items( 'shipping' ) ), 'Precondition: order should have shipping items in the DB.' );

		$order->remove_order_items();

		$reloaded_before_save = wc_get_order( $order_id );
		$this->assertNotEmpty( $reloaded_before_save->get_items(), 'Line items should still be present in the DB before save().' );
		$this->assertNotEmpty( $reloaded_before_save->get_items( 'shipping' ), 'Shipping items should still be present in the DB before save().' );

		$order->save();

		$reloaded_after_save = wc_get_order( $order_id );
		$this->assertCount( 0, $reloaded_after_save->get_items(), 'Line items should be removed from the DB after save().' );
		$this->assertCount( 0, $reloaded_after_save->get_items( 'shipping' ), 'Shipping items should be removed from the DB after save().' );
	}

	/**
	 * @testdox Should keep original items in the DB if save() never runs after remove_order_items().
	 */
	public function test_remove_order_items_preserves_db_items_if_save_not_called() {
		$order    = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$original_line_item_ids = array_keys( $order->get_items() );
		$original_shipping_ids  = array_keys( $order->get_items( 'shipping' ) );

		$order->remove_order_items();

		unset( $order );
		wp_cache_flush();

		$reloaded = wc_get_order( $order_id );
		$this->assertSame(
			$original_line_item_ids,
			array_keys( $reloaded->get_items() ),
			'Line items should remain intact when remove_order_items() is not followed by save().'
		);
		$this->assertSame(
			$original_shipping_ids,
			array_keys( $reloaded->get_items( 'shipping' ) ),
			'Shipping items should remain intact when remove_order_items() is not followed by save().'
		);
	}

	/**
	 * @testdox Should only remove items of the requested type when a type is passed.
	 */
	public function test_remove_order_items_by_type_defers_db_deletion() {
		$order    = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$order->remove_order_items( 'line_item' );

		$before_save = wc_get_order( $order_id );
		$this->assertNotEmpty( $before_save->get_items(), 'Line items should still be in the DB before save().' );
		$this->assertNotEmpty( $before_save->get_items( 'shipping' ), 'Shipping items should still be in the DB before save().' );

		$order->save();

		$after_save = wc_get_order( $order_id );
		$this->assertCount( 0, $after_save->get_items(), 'Line items should be removed after save().' );
		$this->assertNotEmpty( $after_save->get_items( 'shipping' ), 'Shipping items should not be removed when only line_item was requested.' );
	}

	/**
	 * @testdox Pre-hook fires immediately and post-hook fires after the deferred DB delete during save.
	 */
	public function test_remove_order_items_action_hooks_fire_at_correct_times() {
		$order = WC_Helper_Order::create_order();

		$pre_calls    = array();
		$post_calls   = array();
		$expected_log = array(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'line_item',
			),
		);

		$pre_callback  = function ( $fired_order, $type ) use ( &$pre_calls ) {
			$pre_calls[] = array(
				'order_id' => $fired_order->get_id(),
				'type'     => $type,
			);
		};
		$post_callback = function ( $fired_order, $type ) use ( &$post_calls ) {
			$post_calls[] = array(
				'order_id' => $fired_order->get_id(),
				'type'     => $type,
			);
		};

		add_action( 'woocommerce_remove_order_items', $pre_callback, 10, 2 );
		add_action( 'woocommerce_removed_order_items', $post_callback, 10, 2 );

		try {
			$order->remove_order_items( 'line_item' );

			$this->assertSame(
				$expected_log,
				$pre_calls,
				'woocommerce_remove_order_items should fire once when removal is requested.'
			);
			$this->assertSame(
				array(),
				$post_calls,
				'woocommerce_removed_order_items should not fire until the deferred DB delete runs in save().'
			);

			$order->save();

			$this->assertSame(
				$expected_log,
				$post_calls,
				'woocommerce_removed_order_items should fire once with the requested type after save() commits the delete.'
			);
		} finally {
			remove_action( 'woocommerce_remove_order_items', $pre_callback, 10 );
			remove_action( 'woocommerce_removed_order_items', $post_callback, 10 );
		}//end try
	}

	/**
	 * @testdox Post-hook fires once with null type after save() commits a full removal.
	 */
	public function test_remove_order_items_post_hook_for_all_types_fires_with_null_after_save() {
		$order = WC_Helper_Order::create_order();

		$post_calls    = array();
		$post_callback = function ( $fired_order, $type ) use ( &$post_calls ) {
			$post_calls[] = array(
				'order_id' => $fired_order->get_id(),
				'type'     => $type,
			);
		};

		add_action( 'woocommerce_removed_order_items', $post_callback, 10, 2 );

		try {
			$order->remove_order_items();

			$this->assertSame( array(), $post_calls, 'Post-hook should not fire before save().' );

			$order->save();
		} finally {
			remove_action( 'woocommerce_removed_order_items', $post_callback, 10 );
		}

		$this->assertSame(
			array(
				array(
					'order_id' => $order->get_id(),
					'type'     => null,
				),
			),
			$post_calls,
			'Post-hook should fire once with a null type after a full remove_order_items() commits in save().'
		);
	}

	/**
	 * @testdox Should retain queued item types when a post-hook callback throws so a subsequent save() can drain them.
	 */
	public function test_save_items_preserves_queued_types_when_post_hook_throws() {
		$order    = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$order->remove_order_items( 'line_item' );
		$order->remove_order_items( 'shipping' );

		$hook_calls = array();
		$callback   = function ( $fired_order, $type ) use ( &$hook_calls ) {
			$hook_calls[] = $type;
			if ( 'line_item' === $type ) {
				throw new RuntimeException( 'simulated hook failure' );
			}
		};

		add_action( 'woocommerce_removed_order_items', $callback, 10, 2 );

		try {
			$order->save();

			// Intermediate checkpoint: line_item was processed (its post-hook threw),
			// shipping should remain queued. Reload from the data store so we're
			// asserting persisted state rather than the in-memory order.
			$after_first_save = wc_get_order( $order_id );
			$this->assertCount( 0, $after_first_save->get_items(), 'Line items should be removed from the DB after the first save().' );
			$this->assertNotEmpty( $after_first_save->get_items( 'shipping' ), 'Shipping items should remain in the DB until the retry save() drains them.' );
			$this->assertSame( array( 'line_item' ), $hook_calls, 'Only the line_item post-hook should have fired during the first save().' );

			$order->save();
		} finally {
			remove_action( 'woocommerce_removed_order_items', $callback, 10 );
		}

		$reloaded = wc_get_order( $order_id );
		$this->assertCount( 0, $reloaded->get_items(), 'Line items should be removed from the DB across the two saves.' );
		$this->assertCount( 0, $reloaded->get_items( 'shipping' ), 'Shipping items should be removed from the DB on the retry save after the first hook threw.' );
		$this->assertSame(
			array( 'line_item', 'shipping' ),
			$hook_calls,
			'Post-hook should fire for each type exactly once across the two saves, even though the first call threw.'
		);
	}

	/**
	 * @testdox Should ignore non-string item types and trigger _doing_it_wrong.
	 */
	public function test_remove_order_items_rejects_non_string_type() {
		$order = WC_Helper_Order::create_order();

		$this->setExpectedIncorrectUsage( 'WC_Abstract_Order::remove_order_items' );

		$pre_calls = array();
		$pre_hook  = function ( $fired_order, $type ) use ( &$pre_calls ) {
			$pre_calls[] = $type;
		};
		add_action( 'woocommerce_remove_order_items', $pre_hook, 10, 2 );

		try {
			$order->remove_order_items( array( 'line_item' ) );
		} finally {
			remove_action( 'woocommerce_remove_order_items', $pre_hook, 10 );
		}

		$this->assertSame(
			array(),
			$pre_calls,
			'Pre-hook should not fire for a non-string type — the guard returns before the hook.'
		);
		$this->assertNotEmpty( $order->get_items(), 'Line items should remain in memory since the guard ignored the malformed type.' );
	}

	/**
	 * @testdox Should clear in-memory items for extension-registered groups when remove_order_items() is called without a type.
	 */
	public function test_remove_order_items_clears_custom_filter_groups() {
		$order   = WC_Helper_Order::create_order();
		$adjust  = function ( $type_to_group ) {
			$type_to_group['custom_unit_test_type'] = 'custom_unit_test_lines';
			return $type_to_group;
		};
		$reflect = new ReflectionClass( $order );

		add_filter( 'woocommerce_order_type_to_group', $adjust );

		try {
			$items_prop = $reflect->getProperty( 'items' );
			$items_prop->setAccessible( true );
			$items = $items_prop->getValue( $order );

			$items['custom_unit_test_lines'] = array( 'sentinel' );
			$items_prop->setValue( $order, $items );

			$order->remove_order_items();

			$items_after = $items_prop->getValue( $order );
			$this->assertArrayHasKey(
				'custom_unit_test_lines',
				$items_after,
				'Filter-registered groups should be present as keys after the in-memory clear.'
			);
			$this->assertSame(
				array(),
				$items_after['custom_unit_test_lines'],
				'Filter-registered group should be cleared to an empty array — not left with stale entries.'
			);
		} finally {
			remove_filter( 'woocommerce_order_type_to_group', $adjust );
		}
	}

	/**
	 * Register a custom order status so it survives set_status() and is prefixed on save.
	 *
	 * Registers it in the valid order statuses (so set_status keeps it) and as a post status
	 * (so get_post_status adds the wc- prefix, which is what pushes it over the column limit).
	 *
	 * @param string $status Unprefixed order status key.
	 */
	private function register_custom_order_status( string $status ): void {
		$prefixed = 'wc-' . $status;

		add_filter(
			'wc_order_statuses',
			function ( $statuses ) use ( $prefixed ) {
				$statuses[ $prefixed ] = 'Custom status for testing';
				return $statuses;
			}
		);
		register_post_status( $prefixed );
		$this->registered_order_statuses[] = $prefixed;
	}

	/**
	 * @testdox Should warn when an order is saved with a status that exceeds the storage limit.
	 */
	public function test_saving_an_order_with_a_too_long_status_warns() {
		$this->setExpectedIncorrectUsage( 'Abstract_WC_Order_Data_Store_CPT::get_post_status' );

		// 'wc-' + 18 characters = 21, one over the 20-character storage limit.
		$status = str_repeat( 'a', 18 );
		$this->register_custom_order_status( $status );

		$order = WC_Helper_Order::create_order();
		$order->set_status( $status );
		$order->save();
	}

	/**
	 * @testdox Should not warn when an order is saved with a status at the storage limit.
	 */
	public function test_saving_an_order_with_a_status_at_the_limit_does_not_warn() {
		// 'wc-' + 17 characters = 20, exactly the storage limit.
		$status = str_repeat( 'a', 17 );
		$this->register_custom_order_status( $status );

		$order = WC_Helper_Order::create_order();
		$order->set_status( $status );
		$order->save();

		$this->assertSame( $status, wc_get_order( $order->get_id() )->get_status() );
	}
}
