<?php
/**
 * Unit tests for the WC_Shipping_Flat_Rate_Test class.
 *
 * @package WooCommerce\Tests\Shipping.
 */

/**
 * Class WC_Shipping_Flat_Rate_Test
 */
class WC_Shipping_Flat_Rate_Test extends \WC_Unit_Test_Case {

	/**
	 * Test calculate_shipping with products including shipping classes.
	 *
	 * @return void
	 */
	public function test_calculate_shipping_with_shipping_classes() {
		// Create shipping classes.
		$shipping_class_1 = wp_insert_term( 'New York', 'product_shipping_class' );
		$shipping_class_2 = wp_insert_term( 'Florida', 'product_shipping_class' );
		$shipping_class_3 = wp_insert_term( 'California', 'product_shipping_class' );
		$shipping_class_4 = wp_insert_term( 'Illinois', 'product_shipping_class' );

		// Create products and assign to each one of them a different shipping class.
		$product_1 = WC_Helper_Product::create_simple_product(
			false,
			array(
				'name' => 'Test Shipping Product 1',
				'sku'  => 'TSSKU1',
			)
		);
		$product_1->set_shipping_class_id( $shipping_class_1['term_id'] );
		$product_1->save();

		$product_2 = WC_Helper_Product::create_simple_product(
			false,
			array(
				'name' => 'Test Shipping Product 2',
				'sku'  => 'TSSKU2',
			)
		);
		$product_2->set_shipping_class_id( $shipping_class_2['term_id'] );
		$product_2->save();

		$product_3 = WC_Helper_Product::create_simple_product(
			false,
			array(
				'name' => 'Test Shipping Product 3',
				'sku'  => 'TSSKU3',
			)
		);
		$product_3->set_shipping_class_id( $shipping_class_3['term_id'] );
		$product_3->save();

		$product_4 = WC_Helper_Product::create_simple_product(
			false,
			array(
				'name' => 'Test Shipping Product 4',
				'sku'  => 'TSSKU4',
			)
		);
		$product_4->set_shipping_class_id( $shipping_class_4['term_id'] );
		$product_4->save();

		// Create 3 shipping zones. 2 will have one flat rate each while the last will have 2 flat rates.
		$zone_1 = new WC_Shipping_Zone();
		$zone_1->set_zone_name( 'New York' );
		$zone_1->set_zone_order( 1 );
		$zone_1->add_location( 'US:NY', 'state' );
		$instance_id_1 = $zone_1->add_shipping_method( 'flat_rate' );
		$zone_1->save();

		$zone_2 = new WC_Shipping_Zone();
		$zone_2->set_zone_name( 'Florida' );
		$zone_2->set_zone_order( 2 );
		$zone_2->add_location( 'US:FL', 'state' );
		$instance_id_2 = $zone_2->add_shipping_method( 'flat_rate' );
		$zone_2->save();

		$zone_3 = new WC_Shipping_Zone();
		$zone_3->set_zone_name( 'California and Illinois' );
		$zone_3->set_zone_order( 3 );
		$zone_3->add_location( 'US:CA', 'state' );
		$zone_3->add_location( 'US:IL', 'state' );
		$instance_id_3 = $zone_3->add_shipping_method( 'flat_rate' );
		$instance_id_4 = $zone_3->add_shipping_method( 'flat_rate' );
		$zone_3->save();

		$method_cost_1 = '12';
		$method_cost_2 = '34';
		$method_cost_3 = '56';
		$method_cost_4 = '78';

		$map = array(
			$shipping_class_1['term_id'] => array( $instance_id_1, $method_cost_1 ), // NY
			$shipping_class_2['term_id'] => array( $instance_id_2, $method_cost_2 ), // FL
			$shipping_class_3['term_id'] => array( $instance_id_3, $method_cost_3 ), // CA
			$shipping_class_4['term_id'] => array( $instance_id_4, $method_cost_4 ), // IL
		);

		// 2 every flat rate add no cost by default and configure the cost for only one of the shipping classes. Different to each flat rate.
		foreach ( $map as $shipping_class_id => $method ) {
			list( $method_id, $method_cost ) = $method;

			$data = array(
				'woocommerce_flat_rate_title'      => 'Flat rate',
				'woocommerce_flat_rate_tax_status' => 'taxable',
				'woocommerce_flat_rate_cost'       => '',
				'woocommerce_flat_rate_class_cost_' . $shipping_class_1['term_id'] => '',
				'woocommerce_flat_rate_class_cost_' . $shipping_class_2['term_id'] => '',
				'woocommerce_flat_rate_class_cost_' . $shipping_class_3['term_id'] => '',
				'woocommerce_flat_rate_class_cost_' . $shipping_class_4['term_id'] => '',
				'woocommerce_flat_rate_type'       => 'class',
				'instance_id'                      => $method_id,
			);

			$data[ 'woocommerce_flat_rate_class_cost_' . $shipping_class_id ] = $method_cost;

			$shipping_method = WC_Shipping_Zones::get_shipping_method( $method_id );
			$shipping_method->set_post_data( wp_unslash( $data ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_REQUEST['instance_id'] = $method_id;
			$shipping_method->process_admin_options();
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );
		// Set up has finished. Now we need to imitate the different scenarios.

		// Set customer location to US. NY by default.
		WC_Helper_Shipping::force_customer_us_address();

		WC()->cart->empty_cart();

		WC()->cart->add_to_cart( $product_1->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_1, WC()->cart->get_shipping_total(), 'Customers address in in NY and product_1 is shippable to NY.' );

		WC()->cart->add_to_cart( $product_2->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( '0', WC()->cart->get_shipping_total(), 'product_2 is not shippable to NY.' );

		// Set customer location to FL.
		add_filter(
			'woocommerce_customer_get_shipping_state',
			function () {
				return 'FL';
			},
			11
		);

		WC()->cart->empty_cart();

		WC()->cart->add_to_cart( $product_2->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_2, WC()->cart->get_shipping_total() );

		WC()->cart->add_to_cart( $product_1->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( '0', WC()->cart->get_shipping_total(), 'product_1 is not shippable to FL' );

		// Set customer location to CA.
		add_filter(
			'woocommerce_customer_get_shipping_state',
			function () {
				return 'CA';
			},
			12
		);

		WC()->cart->empty_cart();

		WC()->cart->add_to_cart( $product_3->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_3, WC()->cart->get_shipping_total() );

		// Set customer location to IL.
		add_filter(
			'woocommerce_customer_get_shipping_state',
			function () {
				return 'IL';
			},
			13
		);

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_3, WC()->cart->get_shipping_total(), 'product 3 is shippable to illinois and california with a the same rate.' );

		WC()->cart->add_to_cart( $product_4->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( '0', WC()->cart->get_shipping_total(), 'product_4 cant be combined with product_3. Each flat rate has cost only for one of those classes.' );

		// Set customer location to CA.
		add_filter(
			'woocommerce_customer_get_shipping_state',
			function () {
				return 'CA';
			},
			14
		);

		WC()->cart->empty_cart();

		WC()->cart->add_to_cart( $product_4->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_4, WC()->cart->get_shipping_total() );

		// Set customer location to IL.
		add_filter(
			'woocommerce_customer_get_shipping_state',
			function () {
				return 'IL';
			},
			15
		);

		WC()->cart->calculate_shipping();

		$this->assertEquals( $method_cost_4, WC()->cart->get_shipping_total(), 'product 4 is shippable to illinois and california with a the same rate.' );

		WC()->cart->add_to_cart( $product_3->get_id(), 1 );

		WC()->cart->calculate_shipping();

		$this->assertEquals( '0', WC()->cart->get_shipping_total(), 'product_3 cant be combined with product_4. Each flat rate has cost only for one of those classes.' );

		$zone_1->delete( true );
		$zone_2->delete( true );
		$zone_3->delete( true );
	}
}
