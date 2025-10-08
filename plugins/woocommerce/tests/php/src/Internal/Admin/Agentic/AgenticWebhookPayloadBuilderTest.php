<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookPayloadBuilder;
use WC_Order;
use WC_Order_Refund;

/**
 * Unit tests for AgenticWebhookPayloadBuilder class.
 */
class AgenticWebhookPayloadBuilderTest extends \WC_Unit_Test_Case {
	/**
	 * Payload builder instance.
	 *
	 * @var AgenticWebhookPayloadBuilder
	 */
	protected $payload_builder;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->payload_builder = new AgenticWebhookPayloadBuilder();
	}

	/**
	 * Test building a payload for order creation.
	 */
	public function test_build_payload_order_create() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_123' );
		$order->set_status( 'processing' );
		$order->save();

		// Build payload.
		$payload = $this->payload_builder->build_payload( 'order_create', $order );

		// Assert structure.
		$this->assertEquals( 'order_create', $payload['type'] );
		$this->assertArrayHasKey( 'data', $payload );

		// Assert data structure.
		$data = $payload['data'];
		$this->assertEquals( 'order', $data['type'] );
		$this->assertEquals( 'test_session_123', $data['checkout_session_id'] );
		$this->assertStringContainsString( 'order-received', $data['permalink_url'] );
		$this->assertEquals( 'confirmed', $data['status'] );
		$this->assertIsArray( $data['refunds'] );
		$this->assertEmpty( $data['refunds'] );
	}

	/**
	 * Test building a payload for order update.
	 */
	public function test_build_payload_order_update() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_456' );
		$order->set_status( 'completed' );
		$order->save();

		// Build payload.
		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		// Assert structure.
		$this->assertEquals( 'order_update', $payload['type'] );
		$this->assertArrayHasKey( 'data', $payload );

		// Assert data structure.
		$data = $payload['data'];
		$this->assertEquals( 'order', $data['type'] );
		$this->assertEquals( 'test_session_456', $data['checkout_session_id'] );
		$this->assertEquals( 'fulfilled', $data['status'] );
	}

	/**
	 * Test status mapping from WooCommerce to ACP.
	 *
	 * @dataProvider status_mapping_provider
	 */
	public function test_status_mapping( $wc_status, $expected_acp_status ) {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( $wc_status );
		$order->save();

		$payload = $this->payload_builder->build_payload( 'order_update', $order );
		$this->assertEquals( $expected_acp_status, $payload['data']['status'] );
	}

	/**
	 * Provider for status mapping tests.
	 */
	public function status_mapping_provider() {
		return array(
			array( 'pending', 'created' ),
			array( 'processing', 'confirmed' ),
			array( 'on-hold', 'manual_review' ),
			array( 'completed', 'fulfilled' ),
			array( 'cancelled', 'canceled' ),
			array( 'refunded', 'fulfilled' ),
			array( 'failed', 'canceled' ),
		);
	}

	/**
	 * Test building a payload with refunds.
	 */
	public function test_build_payload_with_refunds() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_789' );
		$order->save();

		// Create a refund.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'Product defect',
			)
		);

		// Build payload.
		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		// Assert refunds.
		$this->assertNotEmpty( $payload['data']['refunds'] );
		$this->assertCount( 1, $payload['data']['refunds'] );

		$refund_data = $payload['data']['refunds'][0];
		$this->assertEquals( 'original_payment', $refund_data['type'] );
		$this->assertEquals( '10.00', $refund_data['amount'] );
	}

	/**
	 * Test building a payload with store credit refund.
	 */
	public function test_build_payload_with_store_credit_refund() {
		// Create a test order.
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_agentic_checkout_session_id', 'test_session_999' );
		$order->save();

		// Create a refund with store credit reason.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5.00,
				'reason'   => 'Store credit issued',
			)
		);

		// Build payload.
		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		// Assert refunds.
		$this->assertNotEmpty( $payload['data']['refunds'] );
		$refund_data = $payload['data']['refunds'][0];
		$this->assertEquals( 'store_credit', $refund_data['type'] );
		$this->assertEquals( '5.00', $refund_data['amount'] );
	}

	/**
	 * Test fallback checkout session ID generation.
	 */
	public function test_fallback_checkout_session_id() {
		// Create order without agentic session ID.
		$order = \WC_Helper_Order::create_order();
		$order->save();

		// Build payload.
		$payload = $this->payload_builder->build_payload( 'order_create', $order );

		// Assert fallback session ID.
		$expected_session_id = 'checkout_session_' . $order->get_id();
		$this->assertEquals( $expected_session_id, $payload['data']['checkout_session_id'] );
	}

	/**
	 * Test permalink URL generation.
	 */
	public function test_permalink_url() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$payload = $this->payload_builder->build_payload( 'order_create', $order );

		// Assert URL is valid.
		$this->assertNotEmpty( $payload['data']['permalink_url'] );
		$this->assertStringContainsString( 'http', $payload['data']['permalink_url'] );
	}
}
