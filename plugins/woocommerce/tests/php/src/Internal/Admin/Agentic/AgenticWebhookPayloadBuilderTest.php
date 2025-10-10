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
		$this->payload_builder->init();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_agentic_webhook_order_status_map' );
		remove_all_filters( 'woocommerce_agentic_webhook_refund_type' );
		parent::tearDown();
	}

	/**
	 * Test building payloads for different event types.
	 *
	 * @dataProvider event_type_provider
	 *
	 * @param string $event               Event type.
	 * @param string $status              WooCommerce order status.
	 * @param string $expected_acp_status Expected ACP status.
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
	 *
	 * @param string $wc_status           WooCommerce order status.
	 * @param string $expected_acp_status Expected ACP status.
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
	 * Test refunds are included in payload and default to original_payment.
	 */
	public function test_build_payload_with_refunds() {
		$order = $this->create_agentic_order();

		// Create multiple refunds - all should default to original_payment.
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
				'reason'   => 'Product defect',
			)
		);

		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5.00,
				'reason'   => 'Store credit issued', // Even this defaults to original_payment now.
			)
		);

		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		$this->assertCount( 2, $payload['data']['refunds'] );

		// Both refunds should default to original_payment.
		// Amounts should be in cents (integer).
		$this->assertEquals( 'original_payment', $payload['data']['refunds'][0]['type'] );
		$this->assertEquals( 1000, $payload['data']['refunds'][0]['amount'] );

		$this->assertEquals( 'original_payment', $payload['data']['refunds'][1]['type'] );
		$this->assertEquals( 500, $payload['data']['refunds'][1]['amount'] );
	}

	/**
	 * Test status mapping filter.
	 *
	 * @dataProvider status_filter_provider
	 *
	 * @param callable $filter_callback  Filter callback function.
	 * @param string   $wc_status        WooCommerce order status.
	 * @param string   $expected_status  Expected ACP status.
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
			'override to confirmed'   => array(
				function ( $map ) {
					$map['pending'] = 'confirmed';
					return $map;
				},
				'pending',
				'confirmed',
			),
			'map to shipped'          => array(
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

	/**
	 * Test refund type filter allows customization.
	 */
	public function test_refund_type_filter() {
		$order = $this->create_agentic_order();

		// Create a refund.
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 15.00,
				'reason'   => 'Store credit issued',
			)
		);

		// Hook into the filter to change refund type based on reason.
		add_filter(
			'woocommerce_agentic_webhook_refund_type',
			function ( $type, $refund_obj ) {
				if ( stripos( $refund_obj->get_reason(), 'store credit' ) !== false ) {
					return 'store_credit';
				}
				return $type;
			},
			10,
			2
		);

		$payload = $this->payload_builder->build_payload( 'order_update', $order );

		// Check that refund type was changed to store_credit via filter.
		$this->assertNotEmpty( $payload['data']['refunds'] );
		$this->assertEquals( 'store_credit', $payload['data']['refunds'][0]['type'] );

		// Clean up filter.
		remove_all_filters( 'woocommerce_agentic_webhook_refund_type' );
	}
}
