<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;
use WC_Order;

/**
 * Unit tests for AgenticWebhookManager class.
 */
class AgenticWebhookManagerTest extends \WC_Unit_Test_Case {
	/**
	 * Webhook manager instance.
	 *
	 * @var AgenticWebhookManager
	 */
	protected $webhook_manager;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->webhook_manager = new AgenticWebhookManager();

		// Clean up any existing agent configurations.
		delete_option( 'woocommerce_agentic_agents' );
	}

	/**
	 * Test that webhooks are not sent for orders without session ID.
	 */
	public function test_no_webhook_for_orders_without_session_id() {
		// Create order without session ID.
		$order = \WC_Helper_Order::create_order();
		$order->save();

		// Mock the HTTP request to ensure it's not called.
		add_filter( 'pre_http_request', array( $this, 'fail_if_http_request_made' ), 10, 3 );

		// Trigger order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// If we get here without an exception, the test passes.
		$this->assertTrue( true );

		remove_filter( 'pre_http_request', array( $this, 'fail_if_http_request_made' ), 10 );
	}

	/**
	 * Test that webhooks are sent for orders with session ID.
	 */
	public function test_webhook_sent_for_orders_with_session_id() {
		// Configure a test agent.
		update_option( 'woocommerce_agentic_agents', array( 'test_agent' ) );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_url', 'https://example.com' );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_secret', 'test_secret_key' );
		update_option( 'woocommerce_agentic_agent_test_agent_enabled', true );

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Track if webhook was sent.
		$webhook_sent = false;
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$webhook_sent ) {
				if ( strpos( $url, 'agentic_checkout/webhooks/order_events' ) !== false ) {
					$webhook_sent = true;
					// Return a mock response.
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'body'     => json_encode( array( 'received' => true ) ),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Trigger order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// Assert webhook was sent.
		$this->assertTrue( $webhook_sent );
	}

	/**
	 * Test that disabled agents don't receive webhooks.
	 */
	public function test_disabled_agents_dont_receive_webhooks() {
		// Configure a disabled agent.
		update_option( 'woocommerce_agentic_agents', array( 'test_agent' ) );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_url', 'https://example.com' );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_secret', 'test_secret_key' );
		update_option( 'woocommerce_agentic_agent_test_agent_enabled', false );

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Mock the HTTP request to ensure it's not called.
		add_filter( 'pre_http_request', array( $this, 'fail_if_http_request_made' ), 10, 3 );

		// Trigger order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// If we get here without an exception, the test passes.
		$this->assertTrue( true );

		remove_filter( 'pre_http_request', array( $this, 'fail_if_http_request_made' ), 10 );
	}

	/**
	 * Test webhook sent on order status change.
	 */
	public function test_webhook_sent_on_order_status_change() {
		// Configure a test agent.
		update_option( 'woocommerce_agentic_agents', array( 'test_agent' ) );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_url', 'https://example.com' );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_secret', 'test_secret_key' );
		update_option( 'woocommerce_agentic_agent_test_agent_enabled', true );

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->set_status( 'processing' );
		$order->save();

		// Track webhook calls.
		$webhook_count = 0;
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$webhook_count ) {
				if ( strpos( $url, 'agentic_checkout/webhooks/order_events' ) !== false ) {
					$webhook_count++;
					// Verify it's an update event.
					$body = json_decode( $args['body'], true );
					$this->assertEquals( 'order_update', $body['type'] );
					// Return a mock response.
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'body'     => json_encode( array( 'received' => true ) ),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Change order status.
		$order->set_status( 'completed' );
		$order->save();

		// Assert webhook was sent.
		$this->assertEquals( 1, $webhook_count );
	}

	/**
	 * Test webhook sent on refund.
	 */
	public function test_webhook_sent_on_refund() {
		// Configure a test agent.
		update_option( 'woocommerce_agentic_agents', array( 'test_agent' ) );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_url', 'https://example.com' );
		update_option( 'woocommerce_agentic_agent_test_agent_webhook_secret', 'test_secret_key' );
		update_option( 'woocommerce_agentic_agent_test_agent_enabled', true );

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Track webhook calls.
		$webhook_sent = false;
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$webhook_sent ) {
				if ( strpos( $url, 'agentic_checkout/webhooks/order_events' ) !== false ) {
					$webhook_sent = true;
					// Verify payload contains refund data.
					$body = json_decode( $args['body'], true );
					$this->assertEquals( 'order_update', $body['type'] );
					$this->assertNotEmpty( $body['data']['refunds'] );
					// Return a mock response.
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'body'     => json_encode( array( 'received' => true ) ),
					);
				}
				return $preempt;
			},
			10,
			3
		);

		// Create a refund.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'Test refund',
			)
		);

		// Assert webhook was sent.
		$this->assertTrue( $webhook_sent );
	}

	/**
	 * Helper method to fail test if HTTP request is made.
	 */
	public function fail_if_http_request_made( $preempt, $args, $url ) {
		if ( strpos( $url, 'agentic_checkout/webhooks/order_events' ) !== false ) {
			$this->fail( 'Unexpected HTTP request to webhook endpoint' );
		}
		return $preempt;
	}
}
