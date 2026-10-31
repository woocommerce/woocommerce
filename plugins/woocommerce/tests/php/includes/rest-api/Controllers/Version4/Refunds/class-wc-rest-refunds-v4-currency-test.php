<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Currency precision across v4 refund requests and order refundability.
 */
class WC_REST_Refunds_V4_Currency_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Enable v4 before the REST server registers its routes.
	 */
	public function setUp(): void {
		add_filter( 'woocommerce_admin_features', array( __CLASS__, 'enable_v4' ) );
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_currency', 'JPY' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
	}

	/**
	 * Remove the filter installed before the parent hook snapshot.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			remove_filter( 'woocommerce_admin_features', array( __CLASS__, 'enable_v4' ) );
		}
	}

	/**
	 * Enable the v4 routes.
	 *
	 * @param array $features Enabled features.
	 * @return array
	 */
	public static function enable_v4( array $features ): array {
		$features[] = 'rest-api-v4';
		return $features;
	}

	/**
	 * @testdox V4 previews and creates USD refunds without losing cents in a JPY store.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $quantity_only Whether to derive the total from quantity.
	 */
	public function test_preview_and_create_preserve_order_currency( bool $quantity_only ): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_status( OrderStatus::PROCESSING );
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$item->set_subtotal( 10.25 );
		$item->set_total( 10.25 );
		$order->add_item( $item );
		update_option( 'woocommerce_price_num_decimals', 2 );
		$order->set_total( 10.25 );
		$order->save();
		update_option( 'woocommerce_price_num_decimals', 0 );
		$line = array(
			'line_item_id' => $item->get_id(),
			'quantity'     => 1,
		);
		if ( ! $quantity_only ) {
			$line['refund_total'] = 10.25;
		}
		$params = array(
			'order_id'   => $order->get_id(),
			'line_items' => array( $line ),
		);

		$preview_request = new WP_REST_Request( 'POST', '/wc/v4/refunds/preview' );
		$preview_request->set_body_params( $params );
		$preview = $this->server->dispatch( $preview_request );
		$this->assertSame( 200, $preview->get_status() );
		$this->assertSame( '10.25', $preview->get_data()['total'] );
		$this->assertSame( '10.25', $preview->get_data()['max_refundable'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params( $params );
		$created = $this->server->dispatch( $create_request );
		$this->assertSame( 201, $created->get_status() );
		$refund = wc_get_order( $created->get_data()['id'] );
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertSame( 'USD', $refund->get_currency() );
		$this->assertSame( 10.25, (float) $refund->get_amount() );
		$this->assertSame( -10.25, (float) $refund->get_total() );
		$order = new WC_Order( $order->get_id() );
		$this->assertSame( 0.0, (float) $order->get_remaining_refund_amount() );
		$this->assertSame( 0, wc_get_price_decimals(), 'Refunding another currency must not change store precision.' );
	}

	/**
	 * @testdox The order API keeps a fee or shipping line refundable when twenty-five cents remain.
	 * @testWith ["WC_Order_Item_Fee", "fee_lines"]
	 *           ["WC_Order_Item_Shipping", "shipping_lines"]
	 *
	 * @param string $item_class Line item class.
	 * @param string $response_key Line collection in the response.
	 */
	public function test_order_schema_preserves_fractional_refundability( string $item_class, string $response_key ): void {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_status( OrderStatus::PROCESSING );
		$item = new $item_class();
		$item->set_name( 'Fractional tax remainder' );
		$item->set_total( 10.0 );
		$item->set_taxes( array( 'total' => array( 123 => 0.75 ) ) );
		$order->add_item( $item );
		update_option( 'woocommerce_price_num_decimals', 2 );
		$order->set_total( 10.75 );
		$order->save();
		update_option( 'woocommerce_price_num_decimals', 0 );
		$refunded_item = new $item_class();
		$refunded_item->set_total( -10.0 );
		$refunded_item->set_taxes( array( 'total' => array( 123 => -0.50 ) ) );
		$refunded_item->add_meta_data( '_refunded_item_id', $item->get_id() );
		$refund = new WC_Order_Refund();
		$refund->set_currency( 'USD' );
		$refund->set_parent_id( $order->get_id() );
		$refund->set_amount( 10.50 );
		$refund->set_total( -10.50 );
		$refund->add_item( $refunded_item );
		$refund->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order->get_id() ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['can_be_refunded'] );
		$this->assertCount( 1, $data[ $response_key ] );
		$this->assertTrue( $data[ $response_key ][0]['can_be_refunded'], 'The remaining USD 0.25 must keep this line refundable.' );
	}
}
