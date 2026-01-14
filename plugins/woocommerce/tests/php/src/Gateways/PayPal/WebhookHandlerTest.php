<?php
/**
 * Unit tests for Automattic\WooCommerce\Gateways\PayPal\WebhookHandler class.
 *
 * @package WooCommerce\Tests\Gateways\Paypal
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for testing WordPress globals

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\WebhookHandler as PayPalWebhookHandler;

/**
 * Class WebhookHandlerTest.
 */
class WebhookHandlerTest extends \WC_Unit_Test_Case {

	/**
	 * The webhook handler instance.
	 *
	 * @var PayPalWebhookHandler
	 */
	private $webhook_handler;

	/**
	 * The mock request instance.
	 *
	 * @var \WP_REST_Request
	 */
	private $mock_request;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->webhook_handler = new PayPalWebhookHandler();
		$this->mock_request    = $this->createMock( \WP_REST_Request::class );

		// Prevent real network calls to PayPal during tests.
		add_filter( 'pre_http_request', array( $this, 'mock_paypal_http_response' ) );
	}

	/**
	 * Tear down the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_paypal_http_response' ) );
		$this->webhook_handler = null;
		$this->mock_request    = null;
		parent::tearDown();
	}

	/**
	 * Mock HTTP calls to PayPal endpoints in tests.
	 *
	 * @return array
	 */
	public function mock_paypal_http_response(): array {
		return array( 'response' => array( 'code' => 200 ) );
	}

	/**
	 * Test process_checkout_order_approved with valid data.
	 *
	 * @return void
	 */
	public function test_process_checkout_order_approved_with_valid_data(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'CHECKOUT.ORDER.APPROVED',
			'resource'   => array(
				'id'             => 'PAYPAL_ORDER_123',
				'status'         => 'APPROVED',
				'intent'         => 'CAPTURE',
				'links'          => array(
					array(
						'href'   => 'https://paypal.example.com/v2/checkout/orders/PAYPAL_ORDER_123/capture',
						'rel'    => 'capture',
						'method' => 'POST',
					),
				),
				'purchase_units' => array(
					array(
						'custom_id' => wp_json_encode( $custom_id_data ),
					),
				),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$this->webhook_handler->process_webhook( $this->mock_request );

		// Verify order was updated.
		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( 'APPROVED', $test_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );

		// Clean up.
		$test_order->delete( true );
	}

	/**
	 * Test process_checkout_order_approved skips already processed orders.
	 *
	 * @return void
	 */
	public function test_process_checkout_order_approved_skips_already_processed(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_COMPLETED );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'CHECKOUT.ORDER.APPROVED',
			'resource'   => array(
				'id'             => 'PAYPAL_ORDER_123',
				'status'         => 'APPROVED',
				'intent'         => 'CAPTURE',
				'purchase_units' => array(
					array(
						'custom_id' => wp_json_encode( $custom_id_data ),
					),
				),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$this->webhook_handler->process_webhook( $this->mock_request );

		// Verify order was not updated.
		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( 'COMPLETED', $test_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );

		$test_order->delete( true );
	}

	/**
	 * Test process_payment_capture_completed with valid data.
	 *
	 * @return void
	 */
	public function test_process_payment_capture_completed_with_valid_data(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
			'resource'   => array(
				'id'        => 'CAPTURE_123',
				'status'    => 'COMPLETED',
				'custom_id' => wp_json_encode( $custom_id_data ),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$this->webhook_handler->process_webhook( $this->mock_request );

		// Verify order was updated.
		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( 'COMPLETED', $test_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );

		// Clean up.
		$test_order->delete( true );
	}

	/**
	 * Test process_payment_capture_completed skips already processed orders.
	 *
	 * @return void
	 */
	public function test_process_payment_capture_completed_skips_already_processed(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_COMPLETED );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
			'resource'   => array(
				'id'        => 'CAPTURE_123',
				'status'    => 'COMPLETED',
				'custom_id' => wp_json_encode( $custom_id_data ),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$original_transaction_id = $test_order->get_transaction_id();
		$this->webhook_handler->process_webhook( $this->mock_request );

		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( 'COMPLETED', $test_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
		$this->assertEquals( $original_transaction_id, $test_order->get_transaction_id() );

		$test_order->delete( true );
	}

	/**
	 * Test process_payment_authorization_created with valid data.
	 *
	 * @return void
	 */
	public function test_process_payment_authorization_created_with_valid_data(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'PAYMENT.AUTHORIZATION.CREATED',
			'resource'   => array(
				'id'        => 'AUTH_123',
				'status'    => 'CREATED',
				'custom_id' => wp_json_encode( $custom_id_data ),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$this->webhook_handler->process_webhook( $this->mock_request );

		// Transaction ID and order status should be updated.
		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( 'AUTH_123', $test_order->get_transaction_id() );
		$this->assertEquals( 'on-hold', $test_order->get_status() );

		// Clean up.
		$test_order->delete( true );
	}

	/**
	 * Test process_payment_authorization_created skips already processed orders.
	 *
	 * @return void
	 */
	public function test_process_payment_authorization_created_skips_already_processed(): void {
		$test_order = \WC_Helper_Order::create_order();
		$test_order->set_payment_method( 'paypal' );
		$test_order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_COMPLETED );
		$test_order->save();

		$custom_id_data = array(
			'order_id'  => $test_order->get_id(),
			'order_key' => $test_order->get_order_key(),
			'site_url'  => 'https://example.com',
			'site_id'   => 12345,
		);

		$webhook_data = array(
			'event_type' => 'PAYMENT.AUTHORIZATION.CREATED',
			'resource'   => array(
				'id'        => 'AUTH_123',
				'custom_id' => wp_json_encode( $custom_id_data ),
			),
		);

		$this->mock_request->method( 'get_json_params' )->willReturn( $webhook_data );

		$original_transaction_id = $test_order->get_transaction_id();
		$original_status         = $test_order->get_status();

		$this->webhook_handler->process_webhook( $this->mock_request );

		// Order should not be updated.
		$test_order = wc_get_order( $test_order->get_id() );
		$this->assertEquals( $original_transaction_id, $test_order->get_transaction_id() );
		$this->assertEquals( $original_status, $test_order->get_status() );

		$test_order->delete( true );
	}


	/**
	 * Data provider for get_action_url test scenarios.
	 *
	 * @return array
	 */
	public function provider_get_action_url_scenarios(): array {
		return array(
			'valid_capture_link'   => array(
				'links'    => array(
					array(
						'href'   => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/capture',
						'rel'    => 'capture',
						'method' => 'POST',
					),
				),
				'action'   => 'capture',
				'expected' => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/capture',
			),
			'valid_authorize_link' => array(
				'links'    => array(
					array(
						'href'   => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/authorize',
						'rel'    => 'authorize',
						'method' => 'POST',
					),
				),
				'action'   => 'authorize',
				'expected' => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/authorize',
			),
			'action_not_found'     => array(
				'links'    => array(
					array(
						'href'   => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/capture',
						'rel'    => 'capture',
						'method' => 'POST',
					),
				),
				'action'   => 'refund',
				'expected' => null,
			),
			'invalid_url'          => array(
				'links'    => array(
					array(
						'href'   => 'not-a-valid-url',
						'rel'    => 'capture',
						'method' => 'POST',
					),
				),
				'action'   => 'capture',
				'expected' => null,
			),
			'wrong_method'         => array(
				'links'    => array(
					array(
						'href'   => 'https://api.paypal.com/v2/checkout/orders/ORDER_123/capture',
						'rel'    => 'capture',
						'method' => 'GET',
					),
				),
				'action'   => 'capture',
				'expected' => null,
			),
			'empty_links'          => array(
				'links'    => array(),
				'action'   => 'capture',
				'expected' => null,
			),
		);
	}

	/**
	 * Test get_action_url with various scenarios.
	 *
	 * @dataProvider provider_get_action_url_scenarios
	 *
	 * @param array  $links    The links array.
	 * @param string $action   The action to find.
	 * @param mixed  $expected The expected result.
	 * @return void
	 */
	public function test_get_action_url_scenarios( array $links, string $action, $expected ): void {
		// Use reflection to test private method.
		$reflection = new \ReflectionClass( $this->webhook_handler );
		$method     = $reflection->getMethod( 'get_action_url' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->webhook_handler, array( $links, $action ) );

		$this->assertEquals( $expected, $result );
	}
}
