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
		AgenticWebhookManager::reset_processed_events();
		$this->webhook_manager = new AgenticWebhookManager();
	}

	/**
	 * Test that custom actions are not fired for orders without session ID.
	 */
	public function test_no_action_fired_for_orders_without_session_id() {
		// Create order without session ID.
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

		// Trigger order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// Assert action was NOT fired.
		$this->assertFalse( $action_fired );
	}

	/**
	 * Test that custom actions are fired for orders with session ID.
	 */
	public function test_action_fired_for_orders_with_session_id() {
		// Create order with session ID.
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

		// Trigger order created hook.
		do_action( 'woocommerce_new_order', $order->get_id(), $order );

		// Assert action was fired.
		$this->assertTrue( $action_fired );
	}

	/**
	 * Test that custom action is fired on order status change.
	 */
	public function test_action_fired_on_order_status_change() {
		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->set_status( 'processing' );
		$order->save();

		// Track action calls.
		$action_count = 0;
		add_action(
			'woocommerce_agentic_order_updated',
			function () use ( &$action_count ) {
				$action_count++;
			}
		);

		// Change order status.
		$order->set_status( 'completed' );
		$order->save();

		// Assert action was fired once.
		$this->assertEquals( 1, $action_count );
	}

	/**
	 * Test that custom action is fired on refund.
	 */
	public function test_action_fired_on_refund() {
		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Track action calls.
		$action_fired = false;
		add_action(
			'woocommerce_agentic_order_updated',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		// Create a refund.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'Test refund',
			)
		);

		// Assert action was fired.
		$this->assertTrue( $action_fired );
	}

	/**
	 * Test that custom action is fired on multiple partial refunds.
	 */
	public function test_action_fired_on_multiple_partial_refunds() {
		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Track action calls.
		$action_count = 0;
		add_action(
			'woocommerce_agentic_order_updated',
			function () use ( &$action_count ) {
				$action_count++;
			}
		);

		// Create multiple refunds.
		$refund1 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'First refund',
			)
		);

		$refund2 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5.00,
				'reason'   => 'Second refund',
			)
		);

		$refund3 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 15.00,
				'reason'   => 'Third refund',
			)
		);

		// Assert action was fired for each refund.
		$this->assertEquals( 3, $action_count );
	}

	/**
	 * Test webhook payload contains all refunds after multiple partial refunds.
	 */
	public function test_webhook_payload_contains_all_refunds() {
		// Create a webhook with Agentic topic.
		$webhook = new \WC_Webhook();
		$webhook->set_topic( 'action.woocommerce_agentic_order_updated' );
		$webhook->set_delivery_url( 'https://test.com' );
		$webhook->set_secret( 'test_secret' );
		$webhook->save();

		// Create order with session ID.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->save();

		// Create multiple refunds.
		$refund1 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'First refund',
			)
		);

		$refund2 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5.00,
				'reason'   => 'Second refund',
			)
		);

		$refund3 = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 15.00,
				'reason'   => 'Third refund',
			)
		);

		// Get the webhook payload via the filter.
		$original_payload = array( 'original' => 'data' );
		$modified_payload = apply_filters(
			'woocommerce_webhook_payload',
			$original_payload,
			'order',
			$order->get_id(),
			$webhook->get_id()
		);

		// Verify the payload contains all 3 refunds.
		$this->assertEquals( 'order_update', $modified_payload['type'] );
		$this->assertArrayHasKey( 'refunds', $modified_payload['data'] );
		$this->assertCount( 3, $modified_payload['data']['refunds'] );

		// Verify refund amounts are correct.
		$refund_amounts = array_column( $modified_payload['data']['refunds'], 'amount' );
		$this->assertContains( '10.00', $refund_amounts );
		$this->assertContains( '5.00', $refund_amounts );
		$this->assertContains( '15.00', $refund_amounts );

		// Verify all refunds have a type.
		foreach ( $modified_payload['data']['refunds'] as $refund_data ) {
			$this->assertArrayHasKey( 'type', $refund_data );
			$this->assertContains( $refund_data['type'], array( 'store_credit', 'original_payment' ) );
		}

		// Clean up.
		$webhook->delete( true );
	}
}
