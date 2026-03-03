<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;

/**
 * Tests for AgenticWebhookManager class.
 */
class AgenticWebhookManagerTest extends \WC_Unit_Test_Case {
	use AgenticTestHelpers;

	/**
	 * Webhook manager instance.
	 *
	 * @var AgenticWebhookManager
	 */
	private $webhook_manager;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->webhook_manager = wc_get_container()->get( AgenticWebhookManager::class );

		// During tests, the controller never triggered hooks to be registered. Do it manually.
		$this->webhook_manager->register();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		// Remove any existing hooks to prevent duplicates.
		remove_all_filters( 'woocommerce_webhook_topics' );
		remove_all_actions( 'woocommerce_new_order' );
		remove_all_actions( 'woocommerce_order_status_changed' );
		remove_all_actions( 'woocommerce_order_refunded' );

		parent::tearDown();
	}

	/**
	 * Test that custom webhook topics are registered.
	 */
	public function test_custom_topics_registered() {
		/**
		 * Filters the list of webhook topic hooks.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::register_webhook_topic_names()
		 */
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );

		$this->assertArrayHasKey( AgenticWebhookManager::WEBHOOK_TOPIC, $topics );
		$this->assertEquals( 'Agentic Commerce Protocol: Order created or updated', $topics[ AgenticWebhookManager::WEBHOOK_TOPIC ] );
	}

	/**
	 * Test action firing based on session ID presence.
	 *
	 * @dataProvider action_firing_provider
	 *
	 * @param bool $has_session_id Whether order has session ID.
	 * @param bool $should_fire    Whether action should fire.
	 */
	public function test_action_firing_based_on_session_id( $has_session_id, $should_fire ) {
		// Create order.
		$order = \WC_Helper_Order::create_order();
		if ( $has_session_id ) {
			$order->update_meta_data( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID, 'test_session_123' );
		}
		$order->save();

		// Set up action listener.
		$action_count = 0;
		add_action(
			AgenticWebhookManager::WEBHOOK_ACTION,
			function () use ( &$action_count ) {
				$action_count++;
			}
		);

		/**
		 * Manually trigger the new order action to test the hook.
		 *
		 * @since 10.3.0
		 */
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		$this->assertEquals( $should_fire ? 1 : 0, $action_count );
	}

	/**
	 * Provider for action firing tests.
	 */
	public function action_firing_provider() {
		return array(
			'with session ID'    => array( true, true ),
			'without session ID' => array( false, false ),
		);
	}

	/**
	 * Test that order status changes trigger update action.
	 */
	public function test_order_status_change_triggers_update() {
		$order = $this->create_agentic_order( 'test_session', 'processing' );

		$action_count = 0;
		/**
		 * Fires when an Agentic order is updated.
		 *
		 * @see AgenticWebhookManager::handle_order_status_changed()
		 */
		add_action(
			AgenticWebhookManager::WEBHOOK_ACTION,
			function () use ( &$action_count ) {
				$action_count++;
			}
		);

		$order->set_status( 'completed' );
		$order->save();

		$this->assertEquals( 1, $action_count );
	}

	/**
	 * Test refund events trigger update action.
	 *
	 * @dataProvider refund_test_provider
	 *
	 * @param array $refund_amounts Refund amounts to create.
	 * @param int   $expected_count Expected action count.
	 */
	public function test_refund_triggers_update( $refund_amounts, $expected_count ) {
		$order = $this->create_agentic_order();

		$action_count = 0;
		add_action(
			AgenticWebhookManager::WEBHOOK_ACTION,
			function () use ( &$action_count ) {
				$action_count++;
			}
		);

		foreach ( $refund_amounts as $amount ) {
			wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => $amount,
					'reason'   => 'Test refund',
				)
			);
		}

		$this->assertEquals( $expected_count, $action_count );
	}

	/**
	 * Provider for refund tests.
	 */
	public function refund_test_provider() {
		return array(
			'single refund'    => array( array( 10.00 ), 1 ),
			'multiple refunds' => array( array( 10.00, 5.00, 15.00 ), 3 ),
		);
	}

	/**
	 * Test webhook payload contains all refunds.
	 */
	public function test_webhook_payload_contains_all_refunds() {
		$webhook = $this->create_agentic_webhook();
		$order   = $this->create_agentic_order();
		$this->add_webhook_sent_meta( $order );

		// Create multiple refunds.
		$refund_amounts = array( 10.00, 5.00, 15.00 );
		foreach ( $refund_amounts as $amount ) {
			wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => $amount,
				)
			);
		}

		/**
		 * Filters the webhook payload.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::customize_webhook_payload()
		 */
		$payload = apply_filters(
			'woocommerce_webhook_payload',
			array(),
			'order',
			$order->get_id(),
			$webhook->get_id()
		);

		$this->assertEquals( 'order_update', $payload['type'] );
		$this->assertCount( 3, $payload['data']['refunds'] );

		$refund_amounts_in_payload = array_column( $payload['data']['refunds'], 'amount' );
		// Amounts should be in cents (integer).
		$this->assertContains( 1000, $refund_amounts_in_payload );
		$this->assertContains( 500, $refund_amounts_in_payload );
		$this->assertContains( 1500, $refund_amounts_in_payload );

		$webhook->delete( true );
	}

	/**
	 * Test webhook payload customization for ACP format.
	 */
	public function test_webhook_payload_customization() {
		$webhook = $this->create_agentic_webhook();
		$order   = $this->create_agentic_order( 'test_session_456' );

		/**
		 * Filters the webhook payload.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::customize_webhook_payload()
		 */
		$payload = apply_filters(
			'woocommerce_webhook_payload',
			array( 'original' => 'data' ),
			'order',
			$order->get_id(),
			$webhook->get_id()
		);

		$this->assert_agentic_payload_structure( $payload, 'order_create' );
		$this->assertEquals( 'test_session_456', $payload['data']['checkout_session_id'] );

		$webhook->delete( true );
	}

	/**
	 * Test webhook HTTP args customization for ACP compliance.
	 */
	public function test_webhook_http_args_customization() {
		$webhook = $this->create_agentic_webhook();
		$webhook->set_secret( 'test_secret' );
		$webhook->save();

		$payload       = wp_json_encode( array( 'test' => 'data' ) );
		$original_args = array(
			'headers' => array(),
			'body'    => $payload,
		);

		/**
		 * Filters the webhook HTTP args.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::customize_webhook_http_args()
		 */
		$modified_args = apply_filters(
			'woocommerce_webhook_http_args',
			$original_args,
			null,
			$webhook->get_id()
		);

		// Verify Merchant-Signature was added with correct computed value.
		$this->assertArrayHasKey( 'Merchant-Signature', $modified_args['headers'] );

		// Compute expected signature same way WooCommerce does.
		$expected_signature = base64_encode( hash_hmac( 'sha256', $payload, 'test_secret', true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$this->assertEquals( $expected_signature, $modified_args['headers']['Merchant-Signature'] );

		$webhook->delete( true );
	}

	/**
	 * Test that signature is computed correctly for different payloads.
	 */
	public function test_merchant_signature_computation() {
		$webhook = $this->create_agentic_webhook();
		$webhook->set_secret( 'my_webhook_secret_123' );
		$webhook->save();

		// Test with various payload types.
		$test_cases = array(
			array(
				'payload'     => wp_json_encode( array( 'order_id' => 123 ) ),
				'description' => 'Simple JSON payload',
			),
			array(
				'payload'     => wp_json_encode( array( 'unicode' => '€£¥' ) ),
				'description' => 'Unicode characters',
			),
			array(
				'payload'     => '{"nested":{"data":{"value":true}}}',
				'description' => 'Nested JSON',
			),
		);

		foreach ( $test_cases as $test ) {
			$args = array(
				'headers' => array(),
				'body'    => $test['payload'],
			);

			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			$modified_args = apply_filters(
				'woocommerce_webhook_http_args',
				$args,
				null,
				$webhook->get_id()
			);

			// Verify signature matches expected HMAC-SHA256.
			$expected = base64_encode( hash_hmac( 'sha256', $test['payload'], 'my_webhook_secret_123', true ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$this->assertEquals(
				$expected,
				$modified_args['headers']['Merchant-Signature'],
				'Failed for: ' . $test['description']
			);
		}

		$webhook->delete( true );
	}

	/**
	 * Test that non-Agentic webhooks are not affected.
	 */
	public function test_non_agentic_webhooks_unaffected() {
		// Create a regular WooCommerce webhook.
		$webhook = new \WC_Webhook();
		$webhook->set_topic( 'order.created' ); // Regular WC topic.
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->save();

		$args = array(
			'headers' => array(),
			'body'    => '{"test":"data"}',
		);

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$modified_args = apply_filters(
			'woocommerce_webhook_http_args',
			$args,
			null,
			$webhook->get_id()
		);

		// Should not have Merchant-Signature for non-Agentic webhooks.
		$this->assertArrayNotHasKey( 'Merchant-Signature', $modified_args['headers'] );
		// Should not have empty X-WC-Webhook-Signature.
		$this->assertArrayNotHasKey( 'X-WC-Webhook-Signature', $modified_args['headers'] );

		$webhook->delete( true );
	}

	/**
	 * Test that first event is marked as delivered on successful webhook delivery.
	 */
	public function test_mark_first_event_delivered_success() {
		$webhook = $this->create_agentic_webhook();
		$order   = $this->create_agentic_order( 'test_session_789' );

		// Verify the order doesn't have the meta key set initially.
		$this->assertEmpty( $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		// Simulate successful webhook delivery.
		$http_args = array(
			'headers' => array(),
			'body'    => wp_json_encode( array( 'test' => 'payload' ) ),
		);

		// Mock successful HTTP response.
		$response = array(
			'response' => array( 'code' => 200 ),
		);

		/**
		 * Fires when a webhook is delivered.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::mark_first_event_delivered()
		 */
		do_action(
			'woocommerce_webhook_delivery',
			$http_args,
			$response,
			0.5, // duration.
			$order->get_id(), // arg (order_id).
			$webhook->get_id()
		);

		// Verify the meta key was set to 'sent'.
		$order = wc_get_order( $order->get_id() ); // Refresh order from database.
		$this->assertEquals( 'sent', $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		$webhook->delete( true );
	}

	/**
	 * Test that first event is not marked as delivered on failed webhook delivery.
	 */
	public function test_mark_first_event_delivered_failure() {
		$webhook = $this->create_agentic_webhook();
		$order   = $this->create_agentic_order( 'test_session_456' );

		// Verify the order doesn't have the meta key set initially.
		$this->assertEmpty( $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		// Simulate failed webhook delivery (HTTP error).
		$http_args = array(
			'headers' => array(),
			'body'    => wp_json_encode( array( 'test' => 'payload' ) ),
		);

		// Mock failed HTTP response.
		$response = array(
			'response' => array( 'code' => 500 ),
		);

		/**
		 * Fires when a webhook is delivered.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::mark_first_event_delivered()
		 */
		do_action(
			'woocommerce_webhook_delivery',
			$http_args,
			$response,
			0.5, // duration.
			$order->get_id(), // arg (order_id).
			$webhook->get_id()
		);

		// Verify the meta key was NOT set.
		$order = wc_get_order( $order->get_id() ); // Refresh order from database.
		$this->assertEmpty( $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		$webhook->delete( true );
	}

	/**
	 * Test that first event marking is skipped for non-Agentic webhooks.
	 */
	public function test_mark_first_event_delivered_non_agentic_webhook() {
		// Create a regular WooCommerce webhook (not Agentic).
		$webhook = new \WC_Webhook();
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->save();

		$order = $this->create_agentic_order( 'test_session_123' );

		// Verify the order doesn't have the meta key set initially.
		$this->assertEmpty( $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		// Simulate successful webhook delivery.
		$http_args = array(
			'headers' => array(),
			'body'    => wp_json_encode( array( 'test' => 'payload' ) ),
		);

		$response = array(
			'response' => array( 'code' => 200 ),
		);

		/**
		 * Fires when a webhook is delivered.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::mark_first_event_delivered()
		 */
		do_action(
			'woocommerce_webhook_delivery',
			$http_args,
			$response,
			0.5, // duration.
			$order->get_id(), // arg (order_id).
			$webhook->get_id()
		);

		// Verify the meta key was NOT set for non-Agentic webhook.
		$order = wc_get_order( $order->get_id() ); // Refresh order from database.
		$this->assertEmpty( $order->get_meta( AgenticWebhookManager::FIRST_EVENT_DELIVERED_META_KEY ) );

		$webhook->delete( true );
	}

	/**
	 * Test that first event marking is skipped when order doesn't exist.
	 */
	public function test_mark_first_event_delivered_nonexistent_order() {
		$webhook  = $this->create_agentic_webhook();
		$order    = $this->create_agentic_order( 'test_session_999' );
		$order_id = $order->get_id();

		// Delete the order to simulate non-existent order.
		$order->delete( true );

		// Simulate successful webhook delivery.
		$http_args = array(
			'headers' => array(),
			'body'    => wp_json_encode( array( 'test' => 'payload' ) ),
		);

		$response = array(
			'response' => array( 'code' => 200 ),
		);

		/**
		 * Fires when a webhook is delivered.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::mark_first_event_delivered()
		 */
		do_action(
			'woocommerce_webhook_delivery',
			$http_args,
			$response,
			0.5, // duration.
			$order_id, // arg (order_id) - order no longer exists.
			$webhook->get_id()
		);

		// This should not cause any errors and should complete successfully.
		$this->assertTrue( true ); // If we get here, no exception was thrown.

		$webhook->delete( true );
	}

	/**
	 * Clean up existing Agentic webhooks for testing.
	 */
	private function cleanup_existing_agentic_webhooks() {
		$data_store = \WC_Data_Store::load( 'webhook' );
		$webhooks   = $data_store->search_webhooks(
			array(
				'search' => 'ACP',
				'status' => 'all',
			)
		);

		foreach ( $webhooks as $webhook_id ) {
			$webhook = wc_get_webhook( $webhook_id );
			if ( $webhook ) {
				$webhook->delete( true );
			}
		}
	}
}
