<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use WC_Order;
use WC_Webhook;

/**
 * Shared test helpers for Agentic tests.
 */
trait AgenticTestHelpers {
	/**
	 * Create an order with Agentic session ID.
	 *
	 * @param string $session_id Session ID to use. Defaults to 'test_session_123'.
	 * @param string $status     Order status. Defaults to 'pending'.
	 * @return WC_Order Created order.
	 */
	protected function create_agentic_order( $session_id = 'test_session_123', $status = 'pending' ) {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID, $session_id );
		$order->set_status( $status );
		$order->save();
		return $order;
	}

	/**
	 * Add webhook sent meta to order.
	 *
	 * @param WC_Order $order Order object.
	 */
	protected function add_webhook_sent_meta( $order ) {
		$order->update_meta_data( '_acp_order_created_sent', 'sent' );
		$order->save();
	}

	/**
	 * Create an Agentic webhook.
	 *
	 * @param string $topic Topic for webhook. Defaults to 'action.woocommerce_agentic_order_created'.
	 * @return WC_Webhook Created webhook.
	 */
	protected function create_agentic_webhook( $topic = AgenticWebhookManager::WEBHOOK_TOPIC ) {
		$webhook = new WC_Webhook();
		$webhook->set_topic( $topic );
		$webhook->set_delivery_url( 'https://test.com' );
		$webhook->set_secret( 'test_secret' );
		$webhook->save();
		return $webhook;
	}

	/**
	 * Assert that payload has ACP structure.
	 *
	 * @param array  $payload      Payload to check.
	 * @param string $expected_type Expected type ('order_create' or 'order_update').
	 */
	protected function assert_agentic_payload_structure( $payload, $expected_type ) {
		$this->assertEquals( $expected_type, $payload['type'] );
		$this->assertArrayHasKey( 'data', $payload );
		$this->assertEquals( 'order', $payload['data']['type'] );
		$this->assertArrayHasKey( 'checkout_session_id', $payload['data'] );
		$this->assertArrayHasKey( 'status', $payload['data'] );
		$this->assertArrayHasKey( 'refunds', $payload['data'] );
	}

	/**
	 * Track action firing with a counter.
	 *
	 * @param string $action Action name to track.
	 * @return int Counter reference.
	 */
	protected function track_action( $action ) {
		$count = 0;
		add_action(
			$action,
			function () use ( &$count ) {
				$count++;
			}
		);
		return $count;
	}
}
