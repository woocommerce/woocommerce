<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\PaymentGatewayFeature;

/** Refund precision integration tests. */
class WC_REST_Refund_Currency_Test extends WC_REST_Unit_Test_Case {
	/** @var array Gateway amounts. */
	private $gateway_amounts = array();

	/** Set up a recording gateway. */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$gateway           = $this->getMockBuilder( WC_Payment_Gateway::class )->onlyMethods( array( 'process_refund' ) )->getMock();
		$gateway->id       = 'currency_test';
		$gateway->supports = array( PaymentGatewayFeature::REFUNDS );
		$gateway->method( 'process_refund' )->willReturnCallback(
			function ( $order_id, $amount ) {
				unset( $order_id );
				$this->gateway_amounts[] = (float) $amount;
				return true;
			}
		);
		add_filter(
			'woocommerce_payment_gateways',
			static function () use ( $gateway ) {
				return array( $gateway );
			}
		);
		WC()->payment_gateways()->init();
	}

	/** Restore the gateway singleton after hook cleanup. */
	public function tearDown(): void {
		parent::tearDown();
		WC()->payment_gateways()->init();
	}

	/**
	 * Currency contexts and store-currency controls.
	 * @return array
	 */
	public function currencies(): array {
		return array(
			'USD on JPY' => array( 'JPY', 0, 'USD' ),
			'JPY on JPY' => array( 'JPY', 0, 'JPY' ),
			'USD on USD' => array( 'USD', 2, 'USD' ),
		);
	}

	/**
	 * Computed refunds, including a currency with three decimal places.
	 * @return array
	 */
	public function computed_refunds(): array {
		return array(
			'USD on JPY' => array( 'JPY', 0, 'USD', 10.50, '10.50', 7.50 ),
			'JPY on JPY' => array( 'JPY', 0, 'JPY', 10.50, '11', 7.0 ),
			'USD on USD' => array( 'USD', 2, 'USD', 10.50, '10.50', 7.50 ),
			'KWD on JPY' => array( 'JPY', 0, 'KWD', 10.501, '10.501', 7.499 ),
		);
	}

	/**
	 * Persist checkout-like totals before restoring the configured store precision.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @param float  $net Net total.
	 * @param float  $tax Tax total.
	 * @return array
	 */
	private function order_fixture( string $store, int $dp, string $currency, float $net = 18.0, float $tax = 0.0 ): array {
		update_option( 'woocommerce_currency', $store );
		update_option( 'woocommerce_price_num_decimals', 2 );
		$order = new WC_Order();
		$order->set_currency( $currency );
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_payment_method( 'currency_test' );
		$order->set_date_paid( time() );
		$item = new WC_Order_Item_Product();
		$item->set_name( 'Currency precision fixture' );
		$item->set_quantity( 1 );
		$item->set_subtotal( $net );
		$item->set_total( $net );
		if ( $tax ) {
			$item->set_taxes(
				array(
					'total'    => array( 1 => $tax ),
					'subtotal' => array( 1 => $tax ),
				)
			);
		}
		$order->add_item( $item );
		$order->set_total( $net + $tax );
		$order->save();
		update_option( 'woocommerce_price_num_decimals', $dp );
		return array( wc_get_order( $order->get_id() ), $item->get_id() );
	}

	/**
	 * Dispatch a v3 request containing one refund line.
	 * @param WC_Order $order Order.
	 * @param int      $item_id Original line item ID.
	 * @param array    $line Refund line values.
	 * @param array    $body Top-level request parameters.
	 * @param bool     $preview Preview request.
	 * @return WP_REST_Response
	 */
	private function request( WC_Order $order, int $item_id, array $line, array $body = array(), bool $preview = false ): WP_REST_Response {
		$line[ $preview ? 'line_item_id' : 'id' ] = $item_id;
		$body['line_items']                       = array( $line );
		$request                                  = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' . ( $preview ? '/preview' : '' ) );
		if ( isset( $body['amount'] ) ) {
			$body['amount'] = (string) $body['amount'];
		}
		$request->set_body_params( array_merge( array( 'dp' => 2 ), $body ) );
		return $this->server->dispatch( $request );
	}

	/**
	 * Snapshot independent monetary outputs.
	 * @param WC_Order        $order Order.
	 * @param WC_Order_Refund $refund Refund.
	 * @return array
	 */
	private function amounts( WC_Order $order, WC_Order_Refund $refund ): array {
		$refund = wc_get_order( $refund->get_id() );
		$order  = wc_get_order( $order->get_id() );
		return array(
			'amount'    => (float) $refund->get_amount(),
			'total'     => (float) $refund->get_total(),
			'gateway'   => end( $this->gateway_amounts ),
			'refunded'  => (float) $order->get_total_refunded(),
			'remaining' => (float) $order->get_remaining_refund_amount(),
		);
	}

	/**
	 * @testdox Direct refund persistence agrees with the gateway and remaining amount.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_direct_refund( string $store, int $dp, string $currency ): void {
		list( $order ) = $this->order_fixture( $store, $dp, $currency );
		$refund        = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 5.25,
				'refund_payment' => true,
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$total    = 'JPY' === $currency ? 5.0 : 5.25;
		$refunded = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? $total : 5.25;
		$this->assertEquals(
			array(
				'amount'    => 5.25,
				'total'     => -$total,
				'gateway'   => 5.25,
				'refunded'  => $refunded,
				'remaining' => 18 - $total,
			),
			$this->amounts( $order, $refund )
		);
	}

	/**
	 * @testdox Explicit line totals cannot exceed the gateway refund amount.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_explicit_mismatches( string $store, int $dp, string $currency ): void {
		foreach ( array( array( 5.25, 5 ), array( 0.25, 0 ) ) as $values ) {
			list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency );
			$response                = $this->request(
				$order,
				$item_id,
				array( 'refund_total' => $values[0] ),
				array( 'amount' => $values[1] )
			);
			$this->assertSame( 'JPY' === $currency ? 201 : 400, $response->get_status(), wp_json_encode( $response->get_data() ) );
		}
	}

	/**
	 * @testdox Explicit v3 can refund the last fractional amount.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_explicit_last_fraction( string $store, int $dp, string $currency ): void {
		list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency );
		$first                   = $this->request(
			$order,
			$item_id,
			array( 'refund_total' => 17.75 ),
			array( 'amount' => 17.75 )
		);
		$this->assertSame( 201, $first->get_status(), wp_json_encode( $first->get_data() ) );
		$last = $this->request(
			$order,
			$item_id,
			array( 'refund_total' => 0.25 ),
			array( 'amount' => 0.25 )
		);
		$this->assertSame( 'JPY' === $currency ? 422 : 201, $last->get_status(), wp_json_encode( $last->get_data() ) );
	}

	/**
	 * @testdox Computed refunds and previews preserve the order currency's decimal places.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @param float  $requested Requested refund amount.
	 * @param string $expected_total Expected preview total, including decimal places.
	 * @param float  $remaining Expected remaining refundable amount.
	 * @dataProvider computed_refunds
	 */
	public function test_computed_and_preview( string $store, int $dp, string $currency, float $requested, string $expected_total, float $remaining ): void {
		list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency );
		$preview                 = $this->request(
			$order,
			$item_id,
			array( 'refund_total' => $requested ),
			array(),
			true
		);
		$response                = $this->request(
			$order,
			$item_id,
			array( 'refund_total' => $requested ),
			array( 'compute_totals' => true )
		);
		$this->assertSame( 200, $preview->get_status(), wp_json_encode( $preview->get_data() ) );
		$this->assertSame( $expected_total, $preview->get_data()['total'] );
		$this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$expected = (float) $expected_total;
		$this->assertEquals(
			array(
				'amount'    => $expected,
				'total'     => -$expected,
				'gateway'   => $expected,
				'refunded'  => $expected,
				'remaining' => $remaining,
			),
			$this->amounts( $order, wc_get_order( $response->get_data()['id'] ) )
		);
	}

	/**
	 * @testdox Closing a partial taxed refund uses exact tax history.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_taxed_partial_then_quantity( string $store, int $dp, string $currency ): void {
		list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency, 100, 10 );
		$first                   = $this->request(
			$order,
			$item_id,
			array( 'refund_total' => 55.25 ),
			array( 'compute_totals' => true )
		);
		$this->assertSame( 201, $first->get_status(), wp_json_encode( $first->get_data() ) );
		$preview = $this->request(
			$order,
			$item_id,
			array( 'quantity' => 1 ),
			array(),
			true
		);
		$last    = $this->request(
			$order,
			$item_id,
			array( 'quantity' => 1 ),
			array( 'compute_totals' => true )
		);
		$this->assertSame( 201, $last->get_status(), wp_json_encode( $last->get_data() ) );
		$expected = 'JPY' === $currency ? 55.0 : 54.75;
		$this->assertEquals( array( $expected, $expected, $expected, 110.0 ), array( (float) $last->get_data()['amount'], (float) ( $preview->get_data()['total'] ?? -1 ), end( $this->gateway_amounts ), (float) wc_get_order( $order->get_id() )->get_total_refunded() ) );
	}

	/**
	 * @testdox Closing a tax-only refund preserves the original net amount.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_tax_only_then_close( string $store, int $dp, string $currency ): void {
		list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency, 97.50, 9.75 );
		$first                   = $this->request(
			$order,
			$item_id,
			array(
				'refund_total' => 0,
				'refund_tax'   => array(
					array(
						'id'           => 1,
						'refund_total' => 9.75,
					),
				),
			),
			array( 'amount' => 9.75 )
		);
		$this->assertSame( 201, $first->get_status(), wp_json_encode( $first->get_data() ) );
		$preview = $this->request(
			$order,
			$item_id,
			array( 'quantity' => 1 ),
			array(),
			true
		);
		$last    = $this->request(
			$order,
			$item_id,
			array( 'quantity' => 1 ),
			array( 'compute_totals' => true )
		);
		$this->assertSame( 'JPY' === $currency ? 422 : 201, $last->get_status(), wp_json_encode( $last->get_data() ) );
		if ( 'JPY' !== $currency ) {
			$this->assertEquals( array( 97.50, 97.50, 97.50 ), array( (float) $last->get_data()['amount'], (float) ( $preview->get_data()['total'] ?? -1 ), end( $this->gateway_amounts ) ) );
		}
	}

	/**
	 * @testdox Computed refunds can close the last cents by quantity or amount.
	 * @param string $store Store currency.
	 * @param int    $dp Store decimals.
	 * @param string $currency Order currency.
	 * @dataProvider currencies
	 */
	public function test_computed_last_fraction( string $store, int $dp, string $currency ): void {
		foreach ( array( true, false ) as $quantity_only ) {
			list( $order, $item_id ) = $this->order_fixture( $store, $dp, $currency );
			$first                   = wc_create_refund(
				array(
					'order_id'   => $order->get_id(),
					'amount'     => 17.75,
					'line_items' => array(
						$item_id => array(
							'qty'          => 0,
							'refund_total' => 17.75,
						),
					),
				)
			);
			$this->assertInstanceOf( WC_Order_Refund::class, $first );
			$line     = $quantity_only ? array( 'quantity' => 1 ) : array( 'refund_total' => 0.25 );
			$response = $this->request(
				$order,
				$item_id,
				$line,
				array( 'compute_totals' => true )
			);
			$this->assertSame( 'JPY' === $currency ? 422 : 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
			if ( 'JPY' !== $currency ) {
				$refund = wc_get_order( $response->get_data()['id'] );
				$this->assertSame( 0.25, (float) $refund->get_amount() );
				$this->assertSame( -0.25, (float) $refund->get_total() );
				$this->assertSame( 0.25, end( $this->gateway_amounts ) );
				$this->assertSame( 0.0, (float) wc_get_order( $order->get_id() )->get_remaining_refund_amount() );
			}
		}
	}
}
