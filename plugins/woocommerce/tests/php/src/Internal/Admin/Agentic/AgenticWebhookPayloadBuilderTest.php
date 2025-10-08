<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookPayloadBuilder;

/**
 * Tests for AgenticWebhookPayloadBuilder class.
 */
class AgenticWebhookPayloadBuilderTest extends \WC_Unit_Test_Case {
	use AgenticTestHelpers;

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
	 * Test building payloads for different event types.
	 *
	 * @dataProvider event_type_provider
	 */
	public function test_build_payload_for_event_type( $event, $status, $expected_acp_status ) {
		$order   = $this->create_agentic_order( 'test_session_123', $status );
		$payload = $this->payload_builder->build_payload( $event, $order );

		$this->assert_agentic_payload_structure( $payload, $event );
		$this->assertEquals( 'test_session_123', $payload['data']['checkout_session_id'] );
		$this->assertEquals( $expected_acp_status, $payload['data']['status'] );
		$this->assertEmpty( $payload['data']['refunds'] );
	}

	/**
	 * Provider for event type tests.
	 */
	public function event_type_provider() {
		return array(
			'order create' => array( 'order_create', 'processing', 'confirmed' ),
			'order update' => array( 'order_update', 'completed', 'fulfilled' ),
		);
	}

	/**
	 * Test status mapping from WooCommerce to ACP.
	 *
	 * @dataProvider status_mapping_provider
	 */
	public function test_status_mapping( $wc_status, $expected_acp_status ) {
		$order   = $this->create_agentic_order( 'test_session', $wc_status );
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
	 * Test building payload with refunds.
	 *
	 * @dataProvider refund_type_provider
	 */
	public function test_build_payload_with_refunds( $reason, $expected_type ) {
		$order = $this->create_agentic_order();

		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => $reason,
			)
		);

		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		$this->assertCount( 1, $payload['data']['refunds'] );
		$this->assertEquals( $expected_type, $payload['data']['refunds'][0]['type'] );
		$this->assertEquals( '10.00', $payload['data']['refunds'][0]['amount'] );
	}

	/**
	 * Provider for refund type tests.
	 */
	public function refund_type_provider() {
		return array(
			'original payment' => array( 'Product defect', 'original_payment' ),
			'store credit'     => array( 'Store credit issued', 'store_credit' ),
		);
	}

	/**
	 * Test fallback checkout session ID generation.
	 */
	public function test_fallback_checkout_session_id() {
		$order   = \WC_Helper_Order::create_order();
		$payload = $this->payload_builder->build_payload( 'order_create', $order );

		$expected_session_id = 'checkout_session_' . $order->get_id();
		$this->assertEquals( $expected_session_id, $payload['data']['checkout_session_id'] );
	}

	/**
	 * Test status mapping filter.
	 *
	 * @dataProvider status_filter_provider
	 */
	public function test_status_mapping_filter( $filter_callback, $wc_status, $expected_status ) {
		add_filter( 'woocommerce_agentic_webhook_order_status_map', $filter_callback, 10, 2 );

		$order   = $this->create_agentic_order( 'test_session', $wc_status );
		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		$this->assertEquals( $expected_status, $payload['data']['status'] );

		remove_all_filters( 'woocommerce_agentic_webhook_order_status_map' );
	}

	/**
	 * Provider for status filter tests.
	 */
	public function status_filter_provider() {
		return array(
			'override to confirmed' => array(
				function ( $map ) {
					$map['pending'] = 'confirmed';
					return $map;
				},
				'pending',
				'confirmed',
			),
			'map to shipped'        => array(
				function ( $map ) {
					$map['processing'] = 'shipped';
					return $map;
				},
				'processing',
				'shipped',
			),
			'invalid status fallback' => array(
				function ( $map ) {
					$map['pending'] = 'invalid_status';
					return $map;
				},
				'pending',
				'created', // Should fallback to 'created'.
			),
		);
	}
}
