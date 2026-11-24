<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Constants;
use Automattic\WooCommerce\Gateways\PayPal\Helper;
use Automattic\WooCommerce\Gateways\PayPal\Request;
use Automattic\WooCommerce\Gateways\PayPal\WebhookHandler;

/**
 * Tests that PayPal address echoes cannot replace a priced destination.
 */
class AddressIntegrityTest extends \WC_Unit_Test_Case {
	/**
	 * Last mocked provider order payload.
	 *
	 * @var array
	 */
	private $payload = array();

	/**
	 * Provider details returned to the thank-you fallback.
	 *
	 * @var array
	 */
	private $details = array();

	/**
	 * Number of capture requests.
	 *
	 * @var int
	 */
	private $captures = 0;

	/**
	 * ID returned by the mocked provider for a new order.
	 *
	 * @var string
	 */
	private $provider_order_id = 'TEST-PAYPAL-ORDER';

	/**
	 * Payment gateway registry restored after each test.
	 *
	 * @var array
	 */
	private $gateways;

	/**
	 * Customer singleton restored after each test.
	 *
	 * @var \WC_Customer|null
	 */
	private $original_customer;

	/**
	 * Session singleton replaced while rebuilding the callback cart.
	 *
	 * @var \WC_Session|null
	 */
	private $original_session;

	/**
	 * Set up mocked provider requests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->gateways          = WC()->payment_gateways()->payment_gateways;
		$this->original_customer = WC()->customer;
		$this->original_session  = WC()->session;
		add_filter(
			'pre_option_jetpack_options',
			static function () {
				return array( 'id' => 12345 );
			}
		);
		add_filter(
			'pre_option_jetpack_private_options',
			static function () {
				return array( 'blog_token' => 'TEST.TOKEN' );
			}
		);
		add_filter( 'pre_http_request', array( $this, 'mock_provider' ), 10, 3 );
	}

	/**
	 * Restore the process-wide gateway, customer and session state.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			WC()->payment_gateways()->payment_gateways = $this->gateways;
			WC()->customer                             = $this->original_customer;
			WC()->session                              = $this->original_session;
		}
	}

	/**
	 * Return local provider fixtures for every HTTP request.
	 *
	 * @param mixed  $preempt Previous response.
	 * @param array  $args Request arguments.
	 * @param string $url Request URL.
	 * @return array Mock response.
	 */
	public function mock_provider( $preempt, array $args, string $url ): array {
		unset( $preempt );
		if ( false !== strpos( $url, 'payment/capture' ) || false !== strpos( $url, 'payment/authorize' ) ) {
			++$this->captures;
		}
		if ( 'GET' === $args['method'] ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( $this->details ),
			);
		}
		$body = json_decode( $args['body'] ?? '{}', true );
		if ( isset( $body['order'] ) ) {
			$this->payload = $body['order'];
		}
		return array(
			'response' => array( 'code' => 201 ),
			'body'     => wp_json_encode(
				array(
					'id'     => $this->provider_order_id,
					'status' => 'CREATED',
					'links'  => array(
						array(
							'rel'    => 'approve',
							'method' => 'GET',
							'href'   => 'https://example.com/approve',
						),
					),
				)
			),
		);
	}

	/**
	 * Create a physical order with different billing and shipping addresses.
	 *
	 * @param string $send_shipping Whether shipping is sent.
	 * @param string $override Whether the merchant locks the address.
	 * @return \WC_Order Order with a newly created payment attempt.
	 */
	private function create_attempt( string $send_shipping = 'yes', string $override = 'no' ): \WC_Order {
		update_option(
			'woocommerce_paypal_settings',
			array(
				'send_shipping'    => $send_shipping,
				'address_override' => $override,
			)
		);
		$order = \WC_Helper_Order::create_order();
		$order->set_payment_method( 'paypal' );
		$order->set_status( 'pending' );
		$order->set_shipping_address_1( '10 Delivery Street' );
		$order->set_shipping_city( 'Los Angeles' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '90210' );
		$order->set_shipping_country( 'US' );
		$order->set_billing_address_1( '20 Billing Street' );
		$order->set_billing_city( 'New York' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '10001' );
		$order->set_billing_country( 'US' );
		$order->save();
		$gateway = new \WC_Gateway_Paypal();
		WC()->payment_gateways()->payment_gateways['paypal'] = $gateway;
		$this->assertNotNull( ( new Request( $gateway ) )->create_paypal_order( $order ) );
		return $order;
	}

	/**
	 * Build approval details from the outbound order.
	 *
	 * @return array Provider order details.
	 */
	private function approval(): array {
		return array_merge(
			$this->payload,
			array(
				'id'     => $this->provider_order_id,
				'status' => Constants::STATUS_APPROVED,
				'links'  => array(
					array(
						'rel'    => 'capture',
						'method' => 'POST',
						'href'   => 'https://example.com/capture',
					),
				),
			)
		);
	}

	/**
	 * @testdox Preserves distinct priced addresses despite later setting changes.
	 * @testWith ["no", "no", true]
	 *           ["no", "yes", false]
	 *           ["yes", "yes", false]
	 *           ["yes", "no", true]
	 * @param string $send_shipping Shipping setting.
	 * @param string $override Address lock setting.
	 * @param bool   $callback Whether the attempt permits shipping callbacks.
	 */
	public function test_attempt_policy_survives_setting_changes( string $send_shipping, string $override, bool $callback ): void {
		$order    = $this->create_attempt( $send_shipping, $override );
		$shipping = $order->get_address( 'shipping' );
		$billing  = $order->get_address( 'billing' );
		$total    = $order->get_total();
		$context  = $this->payload['payment_source']['paypal']['experience_context'];
		$this->assertSame( $callback, isset( $context['order_update_callback_config'] ) );
		$this->assertSame( '10 Delivery Street', $this->payload['purchase_units'][0]['shipping']['address']['address_line_1'] );
		update_option(
			'woocommerce_paypal_settings',
			array(
				'send_shipping'    => 'yes' === $send_shipping ? 'no' : 'yes',
				'address_override' => 'yes' === $override ? 'no' : 'yes',
			)
		);
		$details                     = $this->approval();
		$details['payer']['address'] = array(
			'country_code' => 'GB',
			'postal_code'  => 'SW1A 1AA',
		);

		Helper::update_addresses_in_order( $order, $details );
		Helper::update_addresses_in_order( $order, $details );

		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( $billing, $order->get_address( 'billing' ) );
		$this->assertSame( $total, $order->get_total() );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
	}

	/**
	 * @testdox Rejects an unpriced destination before capture, including after the return fallback ran.
	 * @testWith [false]
	 *           [true]
	 * @param bool $return_first Whether the return fallback runs first.
	 */
	public function test_unpriced_approval_stops_capture( bool $return_first ): void {
		$order    = $this->create_attempt();
		$shipping = $order->get_address( 'shipping' );
		$details  = $this->approval();
		if ( $return_first ) {
			Helper::update_addresses_in_order( $order, $details );
		}
		$details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $details );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$this->assertSame( 0, $this->captures );
		$notes = implode( ' ', wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
		$this->assertStringContainsString( 'shipping address', $notes );
		$this->assertStringContainsString( 'skip automatic capture', $notes );
		$request = new Request( new \WC_Gateway_Paypal() );
		$request->authorize_or_capture_payment( $order, 'https://example.com/capture' );
		$request->capture_authorized_payment( $order );
		$this->assertSame( 0, $this->captures );
	}

	/**
	 * Send a local webhook fixture.
	 *
	 * @param string $type Event type.
	 * @param array  $event_resource Provider resource.
	 */
	private function send_webhook( string $type, array $event_resource ): void {
		$request = new \WP_REST_Request( 'POST' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'event_type' => $type,
					'resource'   => $event_resource,
				)
			)
		);
		( new WebhookHandler() )->process_webhook( $request );
	}

	/**
	 * @testdox Keeps a captured mismatched payment on hold without changing totals or claiming a refund.
	 */
	public function test_capture_notification_preserves_review(): void {
		$order   = $this->create_attempt();
		$total   = $order->get_total();
		$details = $this->approval();
		$details['purchase_units'][0]['shipping']['address']['country_code'] = 'GB';
		Helper::update_addresses_in_order( $order, $details );
		$this->send_webhook(
			'PAYMENT.CAPTURE.COMPLETED',
			array(
				'id'        => 'TEST-CAPTURE',
				'status'    => 'COMPLETED',
				'custom_id' => $this->payload['purchase_units'][0]['custom_id'],
			)
		);
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertSame( 'TEST-CAPTURE', $order->get_transaction_id() );
		$this->assertSame( 'COMPLETED', $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertSame( $total, $order->get_total() );
		$this->assertEquals( 0, $order->get_total_refunded() );
	}

	/**
	 * @testdox Notes a skipped capture when a merchant processes an uncaptured order held for address review.
	 * @testWith [false]
	 *           [true]
	 * @param bool $captured Whether PayPal captured the payment before the hold.
	 */
	public function test_processing_held_order_notes_skipped_capture( bool $captured ): void {
		$order   = $this->create_attempt();
		$gateway = $this->getMockBuilder( \WC_Gateway_Paypal::class )->onlyMethods( array( 'should_use_orders_v2' ) )->getMock();
		$gateway->method( 'should_use_orders_v2' )->willReturn( true );
		WC()->payment_gateways()->payment_gateways['paypal'] = $gateway;
		$details = $this->approval();
		$details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $details );
		if ( $captured ) {
			$this->send_webhook(
				'PAYMENT.CAPTURE.COMPLETED',
				array(
					'id'        => 'TEST-HELD-CAPTURE',
					'status'    => Constants::STATUS_COMPLETED,
					'custom_id' => $this->payload['purchase_units'][0]['custom_id'],
				)
			);
		}
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'on-hold', $order->get_status() );

		$order->update_status( 'processing' );

		$notes = implode( ' ', wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
		if ( $captured ) {
			$this->assertStringNotContainsString( 'PayPal payment was not captured', $notes );
		} else {
			$this->assertStringContainsString( 'PayPal payment was not captured', $notes );
		}
		$this->assertSame( 0, $this->captures );
	}

	/**
	 * @testdox A late approval for a paid order keeps merchant address edits and holds only a different destination.
	 * @testWith [false, "processing", ""]
	 *           [true, "on-hold", "yes"]
	 * @param bool   $different Whether the late approval carries a different destination.
	 * @param string $status Expected order status.
	 * @param string $review Expected review flag.
	 */
	public function test_late_approval_after_merchant_address_edit( bool $different, string $status, string $review ): void {
		$order   = $this->create_attempt();
		$details = $this->approval();
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $details );
		$this->send_webhook(
			'PAYMENT.CAPTURE.COMPLETED',
			array(
				'id'        => 'TEST-PAID-CAPTURE',
				'status'    => Constants::STATUS_COMPLETED,
				'custom_id' => $this->payload['purchase_units'][0]['custom_id'],
			)
		);
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'processing', $order->get_status() );
		$order->set_shipping_address_1( '30 Moved Street' );
		$order->set_shipping_postcode( '90211' );
		$order->save();
		if ( $different ) {
			$details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';
		}

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $details );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( $status, $order->get_status() );
		$this->assertSame( $review, $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$this->assertSame( '30 Moved Street', $order->get_shipping_address_1() );
		$this->assertSame( '90211', $order->get_shipping_postcode() );
	}

	/**
	 * @testdox A capture before address confirmation waits for a matching approval or return without capturing twice.
	 * @testWith [false, false]
	 *           [false, true]
	 *           [true, false]
	 *           [true, true]
	 * @param bool $return_path Whether confirmation arrives through the return page.
	 * @param bool $different Whether the provider destination differs.
	 */
	public function test_capture_before_address_confirmation( bool $return_path, bool $different ): void {
		$order    = $this->create_attempt();
		$total    = $order->get_total();
		$shipping = $order->get_address( 'shipping' );
		$capture  = array(
			'id'        => 'TEST-EARLY-CAPTURE',
			'status'    => Constants::STATUS_COMPLETED,
			'custom_id' => $this->payload['purchase_units'][0]['custom_id'],
		);
		$this->send_webhook( 'PAYMENT.CAPTURE.COMPLETED', $capture );
		$this->assertSame( 'on-hold', wc_get_order( $order->get_id() )->get_status(), 'Captured physical orders must wait for their destination to be checked.' );
		$this->details = $this->approval();
		if ( $different ) {
			$this->details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';
		}
		if ( $return_path ) {
			$gateway = $this->getMockBuilder( \WC_Gateway_Paypal::class )->onlyMethods( array( 'should_use_orders_v2' ) )->getMock();
			$gateway->method( 'should_use_orders_v2' )->willReturn( true );
			$gateway->update_addresses_in_order( $order->get_id() );
		} else {
			$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->details );
		}
		$this->send_webhook( 'PAYMENT.CAPTURE.COMPLETED', $capture );
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->details );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( $different ? 'on-hold' : 'processing', $order->get_status(), 'Only a confirmed matching destination may leave the address hold.' );
		$this->assertSame( 'TEST-EARLY-CAPTURE', $order->get_transaction_id() );
		$this->assertSame( Constants::STATUS_COMPLETED, $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( $total, $order->get_total() );
		$this->assertEquals( 0, $order->get_total_refunded() );
		$this->assertSame( 0, $this->captures, 'An already captured payment must not be captured again.' );
	}

	/**
	 * @testdox Address confirmation does not release a merchant hold or reopen a canceled or refunded order.
	 * @testWith ["on-hold"]
	 *           ["cancelled"]
	 *           ["refunded"]
	 * @param string $status Existing merchant status.
	 */
	public function test_capture_preserves_existing_order_disposition( string $status ): void {
		$order = $this->create_attempt();
		$order->set_status( $status );
		$order->save();
		$this->send_webhook(
			'PAYMENT.CAPTURE.COMPLETED',
			array(
				'id'        => 'TEST-HELD-CAPTURE',
				'status'    => Constants::STATUS_COMPLETED,
				'custom_id' => $this->payload['purchase_units'][0]['custom_id'],
			)
		);
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->approval() );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( $status, $order->get_status(), 'Address confirmation must not reverse an unrelated order decision.' );
		$this->assertSame( 'TEST-HELD-CAPTURE', $order->get_transaction_id() );
		$this->assertSame( 0, $this->captures );
	}

	/**
	 * @testdox Allows unchanged legacy shipping but requires review for a different destination.
	 * @testWith [false, ""]
	 *           [true, "yes"]
	 * @param bool   $different Whether the returned location differs.
	 * @param string $review Expected review flag.
	 */
	public function test_legacy_attempt( bool $different, string $review ): void {
		$order    = $this->create_attempt();
		$shipping = $order->get_address( 'shipping' );
		$order->delete_meta_data( Constants::PAYPAL_ORDER_META_ADDRESS_CONTEXT );
		$details = $this->approval();
		if ( $different ) {
			$details['purchase_units'][0]['shipping']['address']['country_code'] = 'GB';
		}
		Helper::update_addresses_in_order( $order, $details );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( $review, $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
	}

	/**
	 * @testdox Records a successful callback quote before allowing final street details.
	 * @testWith ["shipping"]
	 *           ["billing"]
	 * @param string $tax_based_on Tax location setting.
	 */
	public function test_callback_quote_and_duplicate_approval( string $tax_based_on ): void {
		update_option( 'woocommerce_tax_based_on', $tax_based_on );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		foreach ( array(
			'CA' => '10',
			'NY' => '5',
		) as $state => $rate ) {
			\WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'US',
					'tax_rate_state'    => $state,
					'tax_rate'          => $rate,
					'tax_rate_name'     => 'Test tax',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 1,
					'tax_rate_order'    => 1,
					'tax_rate_class'    => '',
				)
			);
		}
		$order = $this->create_attempt();
		$zone  = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'Callback test zone' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$method = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $method . '_settings',
			array(
				'cost'    => '15',
				'enabled' => 'yes',
			)
		);
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		delete_transient( 'wc_shipping_method_count' );
		$request = new \WP_REST_Request( 'POST' );
		$request->set_param( 'id', 'TEST-PAYPAL-ORDER' );
		$request->set_param( 'purchase_units', $this->payload['purchase_units'] );
		$request->set_param(
			'shipping_address',
			array(
				'country_code' => 'US',
				'admin_area_1' => 'CA',
				'admin_area_2' => 'San Francisco',
				'postal_code'  => '94103',
			)
		);
		$request->set_param( 'shipping_option', array( 'id' => 'flat_rate:' . $method ) );
		$controller = new \WC_REST_PayPal_Standard_Controller();
		$response   = $controller->process_shipping_callback( $request );
		$this->assertSame( 200, $response->get_status() );
		$response = $controller->process_shipping_callback( $request );
		$this->assertSame( 200, $response->get_status() );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'NY', WC()->customer->get_billing_state() );
		$this->assertSame( 'CA', WC()->customer->get_shipping_state() );
		$this->assertSame( '15', $order->get_shipping_total() );
		$this->assertSame( 'shipping' === $tax_based_on ? '5.50' : '2.75', wc_format_decimal( $order->get_total_tax(), 2 ) );
		$total                                  = $order->get_total();
		$details                                = $this->approval();
		$details['purchase_units'][0]['amount'] = $response->get_data()['purchase_units'][0]['amount'];
		$details['purchase_units'][0]['shipping']['address'] = array_merge(
			$request->get_param( 'shipping_address' ),
			array(
				'address_line_1' => '30 Approved Street',
				'address_line_2' => '',
			)
		);
		Helper::update_addresses_in_order( $order, $details );
		Helper::update_addresses_in_order( $order, $details );
		$this->assertSame( '30 Approved Street', $order->get_shipping_address_1() );
		$this->assertSame( '94103', $order->get_shipping_postcode() );
		$this->assertSame( $total, $order->get_total() );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$details['purchase_units'][0]['amount']['value'] = '1.00';
		Helper::update_addresses_in_order( $order, $details );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$this->assertSame( $total, $order->get_total() );
		$notes = implode( ' ', wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
		$this->assertStringContainsString( 'payment amount', $notes );
	}

	/**
	 * @testdox Allows a normal payment once with either webhook and thank-you ordering.
	 * @testWith [false]
	 *           [true]
	 * @param bool $return_first Whether the return runs first.
	 */
	public function test_normal_fallback_and_webhook_ordering( bool $return_first ): void {
		$order         = $this->create_attempt( 'no', 'no' );
		$shipping      = $order->get_address( 'shipping' );
		$this->details = $this->approval();
		$gateway       = $this->getMockBuilder( \WC_Gateway_Paypal::class )->onlyMethods( array( 'should_use_orders_v2' ) )->getMock();
		$gateway->method( 'should_use_orders_v2' )->willReturn( true );
		if ( $return_first ) {
			$gateway->update_addresses_in_order( $order->get_id() );
		}
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->details );
		$gateway->update_addresses_in_order( $order->get_id() );
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->details );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$this->assertSame( 1, $this->captures );
	}

	/**
	 * @testdox Keeps a late return mismatch on hold after a payment has been captured.
	 */
	public function test_captured_payment_return_mismatch(): void {
		$order = $this->create_attempt();
		$order->set_transaction_id( 'TEST-CAPTURE' );
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_STATUS, Constants::STATUS_COMPLETED );
		$order->set_status( 'processing' );
		$order->save();
		$shipping      = $order->get_address( 'shipping' );
		$total         = $order->get_total();
		$this->details = $this->approval();
		$this->details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';
		$gateway = $this->getMockBuilder( \WC_Gateway_Paypal::class )->onlyMethods( array( 'should_use_orders_v2' ) )->getMock();
		$gateway->method( 'should_use_orders_v2' )->willReturn( true );
		$gateway->update_addresses_in_order( $order->get_id() );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertSame( 'TEST-CAPTURE', $order->get_transaction_id() );
		$this->assertSame( 'COMPLETED', $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( $total, $order->get_total() );
		$notes = implode( ' ', wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
		$this->assertStringContainsString( 'reported this payment as captured', $notes );
		$this->assertStringNotContainsString( 'skip automatic capture', $notes );
	}

	/**
	 * @testdox Adds the mismatch note when the order is already on hold.
	 */
	public function test_mismatch_note_on_already_held_order(): void {
		$order = $this->create_attempt();
		$order->set_status( 'on-hold' );
		$order->save();
		$notes_before = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$details      = $this->approval();
		$details['purchase_units'][0]['shipping']['address']['postal_code'] = '10001';

		Helper::update_addresses_in_order( $order, $details );

		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$notes_after = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertCount( count( $notes_before ) + 1, $notes_after );
		$this->assertStringContainsString( 'shipping address', $notes_after[0]->content );
		$this->assertStringContainsString( 'skip automatic capture', $notes_after[0]->content );
	}

	/**
	 * @testdox Fills an empty billing location from the PayPal payer on approval.
	 */
	public function test_approval_fills_empty_billing_location(): void {
		$order = $this->create_attempt();
		foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $field ) {
			$order->{"set_billing_{$field}"}( '' );
		}
		$order->save();
		$shipping                    = $order->get_address( 'shipping' );
		$details                     = $this->approval();
		$details['payer']['address'] = array(
			'country_code'   => 'GB',
			'postal_code'    => 'SW1A 1AA',
			'admin_area_2'   => 'London',
			'address_line_1' => '1 Payer Road',
		);

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $details );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'GB', $order->get_billing_country() );
		$this->assertSame( 'SW1A 1AA', $order->get_billing_postcode() );
		$this->assertSame( 'London', $order->get_billing_city() );
		$this->assertSame( '1 Payer Road', $order->get_billing_address_1() );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
	}

	/**
	 * @testdox Skips address imports and shipping callbacks for virtual orders.
	 */
	public function test_virtual_attempt(): void {
		$order = $this->create_attempt();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$product->set_virtual( true );
			$product->save();
		}
		$order = wc_get_order( $order->get_id() );
		$this->assertNotNull( ( new Request( new \WC_Gateway_Paypal() ) )->create_paypal_order( $order ) );
		$shipping = $order->get_address( 'shipping' );
		$context  = $this->payload['payment_source']['paypal']['experience_context'];
		$this->assertSame( Constants::SHIPPING_NO_SHIPPING, $context['shipping_preference'] );
		$this->assertArrayNotHasKey( 'order_update_callback_config', $context );
		Helper::update_addresses_in_order( $order, $this->approval() );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
	}

	/**
	 * @testdox Rejects shipping callbacks when the payment attempt retained merchant shipping.
	 * @testWith ["no", "yes"]
	 *           ["yes", "yes"]
	 * @param string $send_shipping Address source setting.
	 * @param string $override Address lock setting.
	 */
	public function test_callback_cannot_change_preserved_shipping( string $send_shipping, string $override ): void {
		$order    = $this->create_attempt( $send_shipping, $override );
		$shipping = $order->get_address( 'shipping' );
		$request  = new \WP_REST_Request( 'POST' );
		$request->set_param( 'id', 'TEST-PAYPAL-ORDER' );
		$request->set_param( 'purchase_units', $this->payload['purchase_units'] );
		$request->set_param(
			'shipping_address',
			array(
				'country_code' => 'GB',
				'postal_code'  => 'SW1A 1AA',
			)
		);
		$response = ( new \WC_REST_PayPal_Standard_Controller() )->process_shipping_callback( $request );
		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( $shipping, wc_get_order( $order->get_id() )->get_address( 'shipping' ) );
	}

	/**
	 * @testdox Preserves the previous quote when a callback cannot price the new address ($failure).
	 * @testWith ["no_rate"]
	 *           ["cart_rebuild"]
	 *
	 * @param string $failure Why the callback fails.
	 */
	public function test_failed_callback_preserves_priced_destination( string $failure ): void {
		$order = $this->create_attempt();
		if ( 'no_rate' === $failure ) {
			\WC_Helper_Shipping::delete_simple_flat_rate();
		} else {
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product();
				$product->set_stock_status( 'outofstock' );
				$product->save();
			}
		}
		$shipping = $order->get_address( 'shipping' );
		$total    = $order->get_total();
		$context  = $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_CONTEXT );
		$meta     = $this->get_shipping_post_meta( $order->get_id() );
		$request  = new \WP_REST_Request( 'POST' );
		$request->set_param( 'id', 'TEST-PAYPAL-ORDER' );
		$request->set_param( 'purchase_units', $this->payload['purchase_units'] );
		$request->set_param(
			'shipping_address',
			array(
				'country_code' => 'GB',
				'postal_code'  => 'SW1A 1AA',
			)
		);
		$response = ( new \WC_REST_PayPal_Standard_Controller() )->process_shipping_callback( $request );
		$order    = wc_get_order( $order->get_id() );
		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( $shipping, $order->get_address( 'shipping' ) );
		$this->assertSame( $total, $order->get_total() );
		$this->assertSame( $context, $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_CONTEXT ) );
		$this->assertSame( $meta, $this->get_shipping_post_meta( $order->get_id() ), 'Restoring the quote must go through the order data store.' );
	}

	/**
	 * Read the order's shipping post meta, sorted by key.
	 *
	 * The posts data store deletes and re-adds meta on save, so row order is not stable.
	 *
	 * @param int $order_id Order ID.
	 * @return array Shipping post meta keyed by meta key.
	 */
	private function get_shipping_post_meta( int $order_id ): array {
		$meta = array_filter(
			get_post_meta( $order_id ),
			static function ( $key ) {
				return 0 === strpos( $key, '_shipping_' );
			},
			ARRAY_FILTER_USE_KEY
		);
		ksort( $meta );
		return $meta;
	}

	/**
	 * @testdox Starts a replacement attempt with its own source policy and address-update state.
	 */
	public function test_replacement_attempt_resets_address_state(): void {
		$order = $this->create_attempt();
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW, 'yes' );
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED, 'yes' );
		$order->save();
		update_option(
			'woocommerce_paypal_settings',
			array(
				'send_shipping'    => 'no',
				'address_override' => 'yes',
			)
		);
		$this->assertNotNull( ( new Request( new \WC_Gateway_Paypal() ) )->create_paypal_order( $order ) );
		$order   = wc_get_order( $order->get_id() );
		$context = $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_CONTEXT );
		$this->assertSame( 'shipping', $context['address_source'] );
		$this->assertSame( Constants::SHIPPING_SET_PROVIDED_ADDRESS, $context['shipping_preference'] );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESS_REVIEW ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
	}

	/**
	 * @testdox Ignores approval for a replaced PayPal attempt before changing the current attempt.
	 */
	public function test_stale_approval_does_not_confirm_replacement_attempt(): void {
		$order        = $this->create_attempt();
		$old_approval = $this->approval();

		$old_approval['links'][0]['href'] = 'https://example.com/payment/capture';

		$this->provider_order_id = 'TEST-PAYPAL-ORDER-B';
		$this->assertNotNull( ( new Request( new \WC_Gateway_Paypal() ) )->create_paypal_order( $order ) );
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'CURRENT-TOKEN' );
		$order->save();

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $old_approval );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'TEST-PAYPAL-ORDER-B', $order->get_meta( Constants::PAYPAL_ORDER_META_ORDER_ID ) );
		$this->assertSame( 'CREATED', $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
		$this->assertSame( 'CURRENT-TOKEN', $order->get_meta( Constants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN ) );
		$this->assertSame( 0, $this->captures, 'A replaced attempt must not trigger a capture request.' );

		$current_approval = $this->approval();

		$current_approval['links'][0]['href'] = 'https://example.com/payment/capture';
		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $current_approval );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( Constants::STATUS_APPROVED, $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
		$this->assertSame( 1, $this->captures, 'The current attempt must retain its normal capture path.' );
	}

	/**
	 * @testdox Ignores a replaced attempt's approval after the current payment was captured.
	 */
	public function test_stale_approval_does_not_confirm_captured_replacement_attempt(): void {
		$order        = $this->create_attempt();
		$old_approval = $this->approval();

		$this->provider_order_id = 'TEST-PAYPAL-ORDER-B';
		$this->assertNotNull( ( new Request( new \WC_Gateway_Paypal() ) )->create_paypal_order( $order ) );
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_STATUS, Constants::STATUS_COMPLETED );
		$order->update_meta_data( Constants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'CURRENT-TOKEN' );
		$order->save();

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $old_approval );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( Constants::STATUS_COMPLETED, $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertEmpty( $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
		$this->assertSame( 'CURRENT-TOKEN', $order->get_meta( Constants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN ) );

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->approval() );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
	}

	/**
	 * @testdox Preserves approval for an existing attempt without a stored PayPal order ID.
	 */
	public function test_approval_without_stored_paypal_order_id(): void {
		$order = $this->create_attempt();
		$order->delete_meta_data( Constants::PAYPAL_ORDER_META_ORDER_ID );
		$order->save();

		$this->send_webhook( 'CHECKOUT.ORDER.APPROVED', $this->approval() );
		$order = wc_get_order( $order->get_id() );
		$this->assertSame( Constants::STATUS_APPROVED, $order->get_meta( Constants::PAYPAL_ORDER_META_STATUS ) );
		$this->assertSame( 'yes', $order->get_meta( Constants::PAYPAL_ORDER_META_ADDRESSES_UPDATED ) );
	}
}
