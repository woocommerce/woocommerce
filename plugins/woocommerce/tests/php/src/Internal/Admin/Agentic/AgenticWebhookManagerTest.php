<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;

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
		AgenticWebhookManager::reset_processed_events();

		// Remove any existing hooks to prevent duplicates.
		remove_all_filters( 'woocommerce_webhook_topics' );
		remove_all_actions( 'woocommerce_new_order' );
		remove_all_actions( 'woocommerce_order_status_changed' );
		remove_all_actions( 'woocommerce_order_refunded' );
	}

	/**
	 * Test that custom webhook topics are registered.
	 */
	public function test_custom_topics_registered() {
		new AgenticWebhookManager();

		/**
		 * Filters the list of webhook topic hooks.
		 *
		 * @since 10.3.0
		 * @see AgenticWebhookManager::register_webhook_topic_names()
		 */
		$topics = apply_filters( 'woocommerce_webhook_topics', array() );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_created', $topics );
		$this->assertEquals( 'Agentic Order Created', $topics['action.woocommerce_agentic_order_created'] );

		$this->assertArrayHasKey( 'action.woocommerce_agentic_order_updated', $topics );
		$this->assertEquals( 'Agentic Order Updated', $topics['action.woocommerce_agentic_order_updated'] );
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
		new AgenticWebhookManager();

		// Create order.
		$order = \WC_Helper_Order::create_order();
		if ( $has_session_id ) {
			$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		}
		$order->save();

		// Set up action listener.
		$action_count = 0;
		add_action(
			'woocommerce_agentic_order_created',
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
		new AgenticWebhookManager();

		$order = $this->create_agentic_order( 'test_session', 'processing' );

		$action_count = 0;
		/**
		 * Fires when an Agentic order is updated.
		 *
		 * @see AgenticWebhookManager::handle_order_status_changed()
		 */
		add_action(
			'woocommerce_agentic_order_updated',
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
		new AgenticWebhookManager();

		$order = $this->create_agentic_order();

		$action_count = 0;
		add_action(
			'woocommerce_agentic_order_updated',
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
		new AgenticWebhookManager();

		$webhook = $this->create_agentic_webhook( 'action.woocommerce_agentic_order_updated' );
		$order   = $this->create_agentic_order();

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
		$this->assertContains( '10.00', $refund_amounts_in_payload );
		$this->assertContains( '5.00', $refund_amounts_in_payload );
		$this->assertContains( '15.00', $refund_amounts_in_payload );

		$webhook->delete( true );
	}

	/**
	 * Test webhook payload customization for ACP format.
	 */
	public function test_webhook_payload_customization() {
		new AgenticWebhookManager();

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
		new AgenticWebhookManager();

		$webhook = $this->create_agentic_webhook( 'action.woocommerce_agentic_order_updated' );

		$original_args = array(
			'headers' => array(
				'X-WC-Webhook-Signature' => 'test_signature',
			),
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

		// Verify signature header was renamed.
		$this->assertArrayNotHasKey( 'X-WC-Webhook-Signature', $modified_args['headers'] );
		$this->assertArrayHasKey( 'Merchant-Signature', $modified_args['headers'] );
		$this->assertEquals( 'test_signature', $modified_args['headers']['Merchant-Signature'] );

		// Verify ACP headers were added.
		$this->assertArrayHasKey( 'Request-Id', $modified_args['headers'] );
		$this->assertArrayHasKey( 'Timestamp', $modified_args['headers'] );

		$webhook->delete( true );
	}
}
