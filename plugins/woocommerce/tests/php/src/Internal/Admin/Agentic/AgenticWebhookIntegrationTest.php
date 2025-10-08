<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;

/**
 * Integration tests for Agentic Webhook functionality using native WooCommerce webhooks.
 */
class AgenticWebhookIntegrationTest extends \WC_Unit_Test_Case {
	/**
	 * Test that custom webhook topics are registered.
	 */
	public function test_custom_topics_registered() {
		// Get the webhook manager instance to ensure hooks are registered.
		$manager = new AgenticWebhookManager();

		// Check that our topics are registered in the topics filter.
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_created', $topics );
		$this->assertEquals( 'Agentic Order Created', $topics['action.woocommerce_agentic_order_created'] );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_updated', $topics );
		$this->assertEquals( 'Agentic Order Updated', $topics['action.woocommerce_agentic_order_updated'] );
	}

	/**
	 * Test that Agentic orders trigger custom actions.
	 */
	public function test_agentic_order_triggers_custom_action() {
		$manager = new AgenticWebhookManager();

		// Create order with Agentic session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Track if action was fired.
		$action_fired = false;
		add_action(
			'woocommerce_agentic_order_created',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		// Trigger the order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// Assert action was fired.
		$this->assertTrue( $action_fired );
	}

	/**
	 * Test that non-Agentic orders don't trigger custom actions.
	 */
	public function test_non_agentic_order_doesnt_trigger_custom_action() {
		$manager = new AgenticWebhookManager();

		// Create order WITHOUT Agentic session ID.
		$order = \WC_Helper_Order::create_order();
		$order->save();

		// Track if action was fired.
		$action_fired = false;
		add_action(
			'woocommerce_agentic_order_created',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		// Trigger the order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// Assert action was NOT fired.
		$this->assertFalse( $action_fired );
	}

	/**
	 * Test webhook payload customization.
	 */
	public function test_webhook_payload_customization() {
		$manager = new AgenticWebhookManager();

		// Create a real webhook with Agentic topic.
		$webhook = new \WC_Webhook();
		$webhook->set_topic( 'action.woocommerce_agentic_order_created' );
		$webhook->set_delivery_url( 'https://test.com' );
		$webhook->set_secret( 'test_secret' );
		$webhook->save();

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_456' );
		$order->save();

		// Apply the payload filter.
		$original_payload = array( 'original' => 'data' );
		$modified_payload = apply_filters(
			'woocommerce_webhook_payload',
			$original_payload,
			'order',
			$order->get_id(),
			$webhook->get_id()
		);

		// Assert payload was modified to ACP format.
		$this->assertArrayHasKey( 'type', $modified_payload );
		$this->assertEquals( 'order_create', $modified_payload['type'] );
		$this->assertArrayHasKey( 'data', $modified_payload );
		$this->assertEquals( 'order', $modified_payload['data']['type'] );
		$this->assertEquals( 'test_session_456', $modified_payload['data']['checkout_session_id'] );

		// Clean up.
		$webhook->delete( true );
	}

	/**
	 * Test webhook HTTP args customization.
	 */
	public function test_webhook_http_args_customization() {
		$manager = new AgenticWebhookManager();

		// Create a real webhook to test with.
		$webhook = new \WC_Webhook();
		$webhook->set_topic( 'action.woocommerce_agentic_order_updated' );
		$webhook->set_delivery_url( 'https://test.com' );
		$webhook->set_secret( 'test_secret' );
		$webhook->save();

		// Original HTTP args with WooCommerce signature.
		$original_args = array(
			'headers' => array(
				'X-WC-Webhook-Signature' => 'test_signature',
			),
		);

		// Apply the HTTP args filter.
		$modified_args = apply_filters(
			'woocommerce_webhook_http_args',
			$original_args,
			null,
			$webhook->get_id()
		);

		// Assert signature header was renamed.
		$this->assertArrayNotHasKey( 'X-WC-Webhook-Signature', $modified_args['headers'] );
		$this->assertArrayHasKey( 'Merchant-Signature', $modified_args['headers'] );
		$this->assertEquals( 'test_signature', $modified_args['headers']['Merchant-Signature'] );

		// Assert ACP headers were added.
		$this->assertArrayHasKey( 'Request-Id', $modified_args['headers'] );
		$this->assertArrayHasKey( 'Timestamp', $modified_args['headers'] );

		// Clean up.
		$webhook->delete( true );
	}

	/**
	 * Test webhook URL modification.
	 */
	public function test_webhook_url_modification() {
		$manager = new AgenticWebhookManager();

		// Create a mock webhook with Agentic topic.
		$webhook = $this->getMockBuilder( \WC_Webhook::class )
			->disableOriginalConstructor()
			->getMock();

		$webhook->method( 'get_topic' )
			->willReturn( 'action.woocommerce_agentic_order_created' );

		$webhook->method( 'get_id' )
			->willReturn( 789 );

		// Apply the delivery URL filter.
		$original_url = 'https://example.com';
		$modified_url = apply_filters(
			'woocommerce_webhook_delivery_url',
			$original_url,
			789
		);

		// In normal context (not delivery), URL should not be modified.
		$this->assertEquals( 'https://example.com', $modified_url );

		// Note: Testing actual delivery context would require mocking WC_Webhook::deliver()
		// which is complex for this integration test.
	}
}
