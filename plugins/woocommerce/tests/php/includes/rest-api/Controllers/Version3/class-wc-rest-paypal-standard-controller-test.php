<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the POST /wc/v3/paypal-standard/update-shipping endpoint
 * implemented in {@see WC_REST_Paypal_Standard_Controller}.
 *
 * @group paypal
 */
class WC_REST_Paypal_Standard_Controller_Test extends WC_REST_Unit_Test_Case {

	private const PAYPAL_ORDER_ID = 'PAYPAL-ORDER-123';

	private const TEST_ORDER_ITEM_META_KEY = 'test_custom_meta';

	/**
	 * Rate id of the US "Standard" flat rate (cost 10).
	 *
	 * @var string
	 */
	private $us_standard_rate_id;

	/**
	 * Rate id of the US "Express" flat rate (cost 25).
	 *
	 * @var string
	 */
	private $us_express_rate_id;

	/**
	 * Rate id of the Canada flat rate (cost 5).
	 *
	 * @var string
	 */
	private $ca_rate_id;

	/**
	 * The order under test.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Session before the test; the endpoint may replace WC()->session.
	 *
	 * @var WC_Session|null
	 */
	private $previous_session;

	/**
	 * The ID of the product order item meta.
	 *
	 * @var int|null
	 */
	private $product_order_item_meta_id = null;

	/**
	 * Set up shipping zones and a pending PayPal order.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->previous_session = WC()->session;

		$this->product_order_item_meta_id = null;

		add_filter( 'woocommerce_shipping_legacy_flat_rate_is_available', array( $this, 'disable_legacy_flat_rate_shipping' ), 99999 );

		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		// Remove all existing shipping zones.
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			WC_Shipping_Zones::delete_zone( $zone->get_id() );
		}

		$us_zone = new WC_Shipping_Zone();
		$us_zone->set_zone_name( 'US' );
		$us_zone->add_location( 'US', 'country' );
		$us_zone->save();
		$this->us_standard_rate_id = $this->add_flat_rate( $us_zone, 'Standard', '10' );
		$this->us_express_rate_id  = $this->add_flat_rate( $us_zone, 'Express', '25' );

		$ca_zone = new WC_Shipping_Zone();
		$ca_zone->set_zone_name( 'Canada' );
		$ca_zone->add_location( 'CA', 'country' );
		$ca_zone->save();
		$this->ca_rate_id = $this->add_flat_rate( $ca_zone, 'Canada flat rate', '5' );

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product_order_item_id = null;
		$this->order           = $this->create_paypal_order( $this->us_standard_rate_id, self::PAYPAL_ORDER_ID, $product_order_item_id );

		$this->product_order_item_meta_id = $product_order_item_id;
	}

	/**
	 * Restore the customer and session replaced by the endpoint.
	 */
	public function tearDown(): void {
		WC()->session = $this->previous_session;

		parent::tearDown();
	}

	/**
	 * Disable legacy flat rate shipping.
	 *
	 * @param bool $available Whether legacy flat rate shipping is available.
	 * @return false Always disable legacy flat rate shipping.
	 */
	public function disable_legacy_flat_rate_shipping( $available ): bool {
		return false;
	}

	/**
	 * @testdox Should apply the shipping option sent in the request to the order and recompute totals.
	 */
	public function test_applies_requested_shipping_option_to_order(): void {
		$random_meta   = $this->add_test_order_item_meta( $this->order, $this->product_order_item_meta_id, self::TEST_ORDER_ITEM_META_KEY );
		$line_item_ids = array_keys( $this->order->get_items() );

		$response = $this->dispatch_update_shipping(
			$this->get_us_address(),
			array( 'id' => $this->us_express_rate_id )
		);

		$this->assertSame( 200, $response->get_status() );

		$order          = wc_get_order( $this->order->get_id() );
		$shipping_items = $order->get_items( 'shipping' );
		$this->assertCount( 1, $shipping_items, 'The order should have exactly one shipping line' );
		$this->assertSame( $this->us_express_rate_id, $this->get_rate_id( current( $shipping_items ) ) );
		$this->assertSame( 25.0, (float) $order->get_shipping_total() );
		$this->assertSame( 75.0, (float) $order->get_total() );
		$this->assertSame( $line_item_ids, array_keys( $order->get_items() ), 'Product line items should not be recreated' );

		$data = $response->get_data();
		$this->assertSame( '75.00', $data['purchase_units'][0]['amount']['value'] );
		$this->assertSame( '25.00', $data['purchase_units'][0]['amount']['breakdown']['shipping']['value'] );

		$this->assertSame(
			array(
				$this->us_standard_rate_id => false,
				$this->us_express_rate_id  => true,
			),
			array_column( $data['purchase_units'][0]['shipping_options'], 'selected', 'id' )
		);

		$product_order_item = $this->get_product_order_item( $order, $this->product_order_item_meta_id );
		$this->assertSame( $random_meta, $product_order_item->get_meta( self::TEST_ORDER_ITEM_META_KEY ) );
	}

	/**
	 * @testdox Should keep the order's shipping method when the request has no valid shipping option.
	 * @testWith [null]
	 *           ["not-an-array"]
	 *           [[]]
	 *
	 * @param mixed $shipping_option The shipping_option request param, omitted when null.
	 */
	public function test_keeps_order_shipping_method_without_valid_requested_option( $shipping_option ): void {
		$random_meta = $this->add_test_order_item_meta( $this->order, $this->product_order_item_meta_id, self::TEST_ORDER_ITEM_META_KEY );

		$response = $this->dispatch_update_shipping( $this->get_us_address(), $shipping_option );

		$this->assertSame( 200, $response->get_status() );

		$order          = wc_get_order( $this->order->get_id() );
		$shipping_items = $order->get_items( 'shipping' );
		$this->assertCount( 1, $shipping_items, 'The order should have exactly one shipping line' );
		$this->assertSame( $this->us_standard_rate_id, $this->get_rate_id( current( $shipping_items ) ) );
		$this->assertSame( 60.0, (float) $order->get_total() );

		$data = $response->get_data();
		$this->assertSame(
			array(
				$this->us_standard_rate_id => true,
				$this->us_express_rate_id  => false,
			),
			array_column( $data['purchase_units'][0]['shipping_options'], 'selected', 'id' )
		);

		$product_order_item = $this->get_product_order_item( $order, $this->product_order_item_meta_id );
		$this->assertSame( $random_meta, $product_order_item->get_meta( self::TEST_ORDER_ITEM_META_KEY ) );
	}

	/**
	 * @testdox Should apply the first available shipping option when the order's method is unavailable at the new address.
	 */
	public function test_applies_first_option_when_order_method_unavailable_for_new_address(): void {
		$random_meta = $this->add_test_order_item_meta( $this->order, $this->product_order_item_meta_id, self::TEST_ORDER_ITEM_META_KEY );

		$response = $this->dispatch_update_shipping( $this->get_canada_address(), null );

		$this->assertSame( 200, $response->get_status() );

		$order          = wc_get_order( $this->order->get_id() );
		$shipping_items = $order->get_items( 'shipping' );
		$this->assertCount( 1, $shipping_items, 'The order should have exactly one shipping line' );
		$this->assertSame( $this->ca_rate_id, $this->get_rate_id( current( $shipping_items ) ) );
		$this->assertSame( 'CA', $order->get_shipping_country() );
		$this->assertSame( 55.0, (float) $order->get_total() );

		$data = $response->get_data();
		$this->assertSame(
			array( $this->ca_rate_id => true ),
			array_column( $data['purchase_units'][0]['shipping_options'], 'selected', 'id' )
		);

		$product_order_item = $this->get_product_order_item( $order, $this->product_order_item_meta_id );
		$this->assertSame( $random_meta, $product_order_item->get_meta( self::TEST_ORDER_ITEM_META_KEY ) );
	}

	/**
	 * @testdox Should recalculate taxes based on the updated shipping address.
	 */
	public function test_recalculates_taxes_for_new_address(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'CA',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'GST',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$random_meta = $this->add_test_order_item_meta( $this->order, $this->product_order_item_meta_id, self::TEST_ORDER_ITEM_META_KEY );

		$response = $this->dispatch_update_shipping( $this->get_canada_address(), null );

		$this->assertSame( 200, $response->get_status() );

		$order = wc_get_order( $this->order->get_id() );
		$this->assertSame( 5.5, (float) $order->get_total_tax(), 'Tax should apply to both the item and the shipping line' );
		$this->assertSame( 60.5, (float) $order->get_total() );

		$data = $response->get_data();
		$this->assertSame( '5.50', $data['purchase_units'][0]['amount']['breakdown']['tax_total']['value'] );
		$this->assertSame( '60.50', $data['purchase_units'][0]['amount']['value'] );

		$product_order_item = $this->get_product_order_item( $order, $this->product_order_item_meta_id );
		$this->assertSame( $random_meta, $product_order_item->get_meta( self::TEST_ORDER_ITEM_META_KEY ) );
	}

	/**
	 * @testdox Should preserve an existing custom product price when updating the address and shipping method.
	 */
	public function test_existing_custom_product_price_is_preserved(): void {
		$order_line_item = $this->get_product_order_item( $this->order, $this->product_order_item_meta_id );
		$order_line_item->set_subtotal( '40.00' );
		$order_line_item->set_total( '40.00' );
		$order_line_item->save();

		// Reload order to get the updated line item details.
		$this->order = wc_get_order( $this->order->get_id() );
		$this->order->calculate_totals( true );
		$this->order->save();

		$initial_order_total = $this->order->get_total();

		$response = $this->dispatch_update_shipping( $this->get_us_address(), $this->us_standard_rate_id );

		$this->assertSame( 200, $response->get_status() );

		$order                   = wc_get_order( $this->order->get_id() );
		$updated_order_line_item = $this->get_product_order_item( $order, $this->product_order_item_meta_id );
		$this->assertSame( '40.00', $updated_order_line_item->get_total() );
		$this->assertSame( $initial_order_total, $order->get_total() );
	}

	/**
	 * @testdox Should preserve fee lines on the order when updating shipping.
	 */
	public function test_preserves_fee_lines(): void {
		$fee = new WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_total( '3' );
		$this->order->add_item( $fee );
		$this->order->calculate_totals( false );
		$this->order->save();

		$response = $this->dispatch_update_shipping(
			$this->get_us_address(),
			array( 'id' => $this->us_express_rate_id )
		);

		$this->assertSame( 200, $response->get_status() );

		$order = wc_get_order( $this->order->get_id() );
		$fees  = $order->get_items( 'fee' );
		$this->assertCount( 1, $fees, 'The fee line should be kept' );
		$this->assertSame( 'Handling', current( $fees )->get_name() );
		$this->assertSame( 78.0, (float) $order->get_total() );
	}

	/**
	 * @testdox Should rebuild the cart with the order's items, variation attributes, prices, fees, and coupons.
	 */
	public function test_rebuilt_cart_matches_order(): void {
		$simple_product = WC_Helper_Product::create_simple_product();
		$simple_product->set_regular_price( '50' );
		$simple_product->save();

		$variation = $this->get_any_number_variation();

		WC_Helper_Coupon::create_coupon(
			'paypal-five-off',
			array(
				'discount_type' => 'fixed_cart',
				'coupon_amount' => '5',
			)
		);

		$order             = wc_create_order();
		$simple_item_id    = $this->add_product_line( $order, $simple_product, 2, 30.0 );
		$variation_item_id = $this->add_product_line(
			$order,
			$variation,
			1,
			12.0,
			array(
				'pa_size'   => 'huge',
				'pa_colour' => 'blue',
				'pa_number' => '1',
			)
		);
		$fee               = new WC_Order_Item_Fee();
		$fee->set_name( 'Handling' );
		$fee->set_amount( '3' );
		$fee->set_total( '3' );
		$order->add_item( $fee );
		$order->apply_coupon( 'paypal-five-off' );
		$this->finalize_paypal_order( $order, $this->us_standard_rate_id, self::PAYPAL_ORDER_ID );
		$this->order = $order;

		$response = $this->dispatch_update_shipping( $this->get_us_address(), array( 'id' => $this->us_standard_rate_id ) );

		$this->assertSame( 200, $response->get_status() );

		$cart_items = array_values( WC()->cart->get_cart() );
		$this->assertCount( 2, $cart_items, 'The cart should contain one entry per order line item' );

		$simple_cart_item = $cart_items[0];
		$this->assertSame( $simple_product->get_id(), $simple_cart_item['product_id'] );
		$this->assertSame( 0, $simple_cart_item['variation_id'] );
		$this->assertSame( 2, $simple_cart_item['quantity'] );
		$this->assertSame( 30.0, (float) $simple_cart_item['data']->get_price(), 'The cart price should match the order price' );

		$variation_cart_item = $cart_items[1];
		$this->assertSame( $variation->get_parent_id(), $variation_cart_item['product_id'] );
		$this->assertSame( $variation->get_id(), $variation_cart_item['variation_id'] );
		$this->assertSame( 1, $variation_cart_item['quantity'] );
		$this->assertSame( 12.0, (float) $variation_cart_item['data']->get_price(), 'The cart price should match the order price' );
		$this->assertSame(
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'blue',
				'attribute_pa_number' => '1',
			),
			$variation_cart_item['variation'],
			'The cart item should include the variation attributes from the order item, including the "Any" attribute value'
		);

		$this->assertSame( array( 'paypal-five-off' ), WC()->cart->get_applied_coupons() );

		$cart_fees = array_values( WC()->cart->get_fees() );
		$this->assertCount( 1, $cart_fees, 'The cart should contain the order fee' );
		$this->assertSame( 'Handling', $cart_fees[0]->name );
		$this->assertSame( 3.0, (float) $cart_fees[0]->amount );

		$this->assertSame( 72.0, (float) WC()->cart->get_subtotal() );
		$this->assertSame( 5.0, (float) WC()->cart->get_discount_total() );

		$updated_order = wc_get_order( $order->get_id() );
		$this->assertSame( array( $simple_item_id, $variation_item_id ), array_keys( $updated_order->get_items() ), 'Product line items should not be recreated' );
		$this->assertSame( 80.0, (float) $updated_order->get_total(), 'Order total should be 72 - 5 + 3 fee + 10 shipping' );
	}

	/**
	 * @testdox Should offer Free Shipping when the order total meets the minimum even though catalog prices do not.
	 */
	public function test_free_shipping_minimum_uses_order_prices(): void {
		$us_zone = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => 'US',
					'state'    => '',
					'postcode' => '',
				),
			)
		);

		$free_shipping_instance_id = $us_zone->add_shipping_method( 'free_shipping' );
		update_option(
			"woocommerce_free_shipping_{$free_shipping_instance_id}_settings",
			array(
				'title'            => 'Free shipping',
				'requires'         => 'min_amount',
				'min_amount'       => '100',
				'ignore_discounts' => 'no',
			)
		);
		$free_shipping_rate_id = "free_shipping:{$free_shipping_instance_id}";
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$first_product = WC_Helper_Product::create_simple_product();
		$first_product->set_regular_price( '50' );
		$first_product->save();
		$second_product = WC_Helper_Product::create_simple_product();
		$second_product->set_regular_price( '20' );
		$second_product->save();
		WC_Helper_Coupon::create_coupon(
			'paypal-five-off',
			array(
				'discount_type' => 'fixed_cart',
				'coupon_amount' => '5',
			)
		);

		// Order subtotal after the coupon is 105, above the 100 minimum; at catalog prices it would be 65.
		$order = wc_create_order();
		$this->add_product_line( $order, $first_product, 1, 70.0 );
		$this->add_product_line( $order, $second_product, 1, 40.0 );
		$order->apply_coupon( 'paypal-five-off' );
		$this->finalize_paypal_order( $order, $this->us_standard_rate_id, self::PAYPAL_ORDER_ID );
		$this->order = $order;

		$response = $this->dispatch_update_shipping( $this->get_us_address(), array( 'id' => $free_shipping_rate_id ) );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame(
			array(
				$this->us_standard_rate_id => false,
				$this->us_express_rate_id  => false,
				$free_shipping_rate_id     => true,
			),
			array_column( $data['purchase_units'][0]['shipping_options'], 'selected', 'id' ),
			'Free shipping should be offered and selected'
		);

		$updated_order  = wc_get_order( $order->get_id() );
		$shipping_items = $updated_order->get_items( 'shipping' );
		$this->assertCount( 1, $shipping_items, 'The order should have exactly one shipping line' );
		$this->assertSame( $free_shipping_rate_id, $this->get_rate_id( current( $shipping_items ) ) );
		$this->assertSame( 0.0, (float) $updated_order->get_shipping_total() );
		$this->assertSame( 105.0, (float) $updated_order->get_total() );
		$this->assertSame( '105.00', $data['purchase_units'][0]['amount']['value'] );
	}

	/**
	 * Add a flat rate shipping method with the given title and cost to a zone.
	 *
	 * @param WC_Shipping_Zone $zone  The zone.
	 * @param string           $title The method title.
	 * @param string           $cost  The method cost.
	 * @return string The rate id.
	 */
	private function add_flat_rate( WC_Shipping_Zone $zone, string $title, string $cost ): string {
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			"woocommerce_flat_rate_{$instance_id}_settings",
			array(
				'title'      => $title,
				'tax_status' => 'taxable',
				'cost'       => $cost,
			)
		);

		return "flat_rate:{$instance_id}";
	}

	/**
	 * Create a pending order for a 50.00 product shipped with the US standard rate.
	 *
	 * @param string   $shipping_rate_id       The shipping rate ID to use for the order.
	 * @param string   $paypal_order_id        The PayPal order ID to set for the order.
	 * @param int|null &$product_order_item_id The ID of the product order item that gets created.
	 * @param string   $regular_price          The regular price of the product.
	 * @param string   $shipping_cost          The shipping cost of the order.
	 * @return WC_Order
	 */
	private function create_paypal_order( string $shipping_rate_id, string $paypal_order_id, ?int &$product_order_item_id, string $regular_price = '50', string $shipping_cost = '10' ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $regular_price );
		$product->save();

		$order                 = wc_create_order();
		$product_order_item_id = $order->add_product( $product, 1 );

		$this->finalize_paypal_order( $order, $shipping_rate_id, $paypal_order_id, $shipping_cost );

		return $order;
	}

	/**
	 * Add a US shipping address and a shipping line to an order, mark it pending and link it to a PayPal order.
	 *
	 * @param WC_Order $order            The order, with its line items already added.
	 * @param string   $shipping_rate_id The shipping rate ID to use for the order.
	 * @param string   $paypal_order_id  The PayPal order ID to set for the order.
	 * @param string   $shipping_cost    The shipping cost of the order.
	 */
	private function finalize_paypal_order( WC_Order $order, string $shipping_rate_id, string $paypal_order_id, string $shipping_cost = '10' ): void {
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '90210' );
		$order->set_shipping_city( 'Beverly Hills' );

		list( $method_id, $instance_id ) = explode( ':', $shipping_rate_id );
		$shipping                        = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Standard' );
		$shipping->set_method_id( $method_id );
		$shipping->set_instance_id( $instance_id );
		$shipping->set_total( $shipping_cost );
		$order->add_item( $shipping );

		$order->set_status( OrderStatus::PENDING );
		$order->update_meta_data( '_paypal_order_id', $paypal_order_id );
		$order->calculate_totals( true );
		$order->save();
	}

	/**
	 * Add a product line to an order at a unit price that may differ from the catalog price.
	 *
	 * @param WC_Order   $order      The order.
	 * @param WC_Product $product    The product or variation.
	 * @param int        $quantity   The quantity.
	 * @param float      $unit_price The unit price charged on the order.
	 * @param array      $variation  Variation attributes to store on the line, keyed by attribute name.
	 * @return int The order item ID.
	 */
	private function add_product_line( WC_Order $order, WC_Product $product, int $quantity, float $unit_price, array $variation = array() ): int {
		$line_total = (string) ( $unit_price * $quantity );
		$args       = array(
			'subtotal' => $line_total,
			'total'    => $line_total,
		);
		if ( ! empty( $variation ) ) {
			$args['variation'] = $variation;
		}

		return $order->add_product( $product, $quantity, $args );
	}

	/**
	 * Get the variation of the helper variable product that has an "Any" number attribute.
	 *
	 * @return WC_Product_Variation
	 * @throws Exception If the variation is not found.
	 */
	private function get_any_number_variation(): WC_Product_Variation {
		$variable_product = WC_Helper_Product::create_variation_product();
		foreach ( $variable_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( $variation instanceof WC_Product_Variation && 'DUMMY SKU VARIABLE HUGE BLUE ANY NUMBER' === $variation->get_sku() ) {
				return $variation;
			}
		}
		throw new Exception( 'Variation with an "Any" attribute not found' );
	}

	/**
	 * Dispatch an update-shipping request for the test order.
	 *
	 * @param array $shipping_address The PayPal shipping address.
	 * @param mixed $shipping_option  The PayPal shipping option, omitted when null.
	 * @return WP_REST_Response
	 */
	private function dispatch_update_shipping( array $shipping_address, $shipping_option ): WP_REST_Response {
		$body = array(
			'id'               => self::PAYPAL_ORDER_ID,
			'shipping_address' => $shipping_address,
			'purchase_units'   => array(
				array(
					'reference_id' => 'default',
					'custom_id'    => wp_json_encode(
						array(
							'order_id'  => $this->order->get_id(),
							'order_key' => $this->order->get_order_key(),
						)
					),
				),
			),
		);
		if ( null !== $shipping_option ) {
			$body['shipping_option'] = $shipping_option;
		}

		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-standard/update-shipping' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Add a metadata value to order item meta to the order.
	 *
	 * @param WC_Order $order                 The order to add the meta to.
	 * @param int|null $product_order_item_id The ID of the product order item to add the meta to.
	 * @param string   $meta_key              The meta key to store the value under.
	 * @return string The random meta value.
	 * @throws Exception If the product order item is not found.
	 */
	private function add_test_order_item_meta( WC_Order $order, ?int $product_order_item_id, string $meta_key ): string {
		$random_meta        = wp_generate_uuid4();
		$product_order_item = $this->get_product_order_item( $order, $product_order_item_id );

		$product_order_item->update_meta_data( $meta_key, $random_meta );
		$product_order_item->save();

		return $random_meta;
	}

	/**
	 * Get the product order item for the test order.
	 *
	 * @param WC_Order $order                 The order to get the product order item from.
	 * @param int|null $product_order_item_id The ID of the product order item to get.
	 * @return WC_Order_Item_Product
	 * @throws Exception If the product order item is not found.
	 */
	private function get_product_order_item( WC_Order $order, ?int $product_order_item_id ): WC_Order_Item_Product {
		if ( null !== $product_order_item_id ) {
			return $order->get_item( $product_order_item_id );
		}
		$line_items          = $order->get_items( 'line_item' );
		$product_order_items = array_filter( $line_items, static fn( $item ) => $item instanceof WC_Order_Item_Product );
		if ( empty( $product_order_items ) ) {
			throw new Exception( 'No product order items found' );
		}
		$product_order_item = reset( $product_order_items );
		if ( ! ( $product_order_item instanceof WC_Order_Item_Product ) ) {
			throw new Exception( 'Product order item not found' );
		}
		return $product_order_item;
	}

	/**
	 * Get the rate id for an order shipping line.
	 *
	 * @param WC_Order_Item_Shipping $item The shipping line.
	 * @return string
	 */
	private function get_rate_id( WC_Order_Item_Shipping $item ): string {
		return $item->get_method_id() . ':' . $item->get_instance_id();
	}

	/**
	 * A PayPal shipping address in the US.
	 *
	 * @return array
	 */
	private function get_us_address(): array {
		return array(
			'country_code' => 'US',
			'admin_area_1' => 'NY',
			'admin_area_2' => 'New York',
			'postal_code'  => '10001',
		);
	}

	/**
	 * A PayPal shipping address in Canada.
	 *
	 * @return array
	 */
	private function get_canada_address(): array {
		return array(
			'country_code' => 'CA',
			'admin_area_1' => 'ON',
			'admin_area_2' => 'Toronto',
			'postal_code'  => 'M5V 3L9',
		);
	}
}
