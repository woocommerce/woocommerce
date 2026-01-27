<?php
/**
 * FraudProtectionDispatcherTest class file.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\DecisionHandler;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * Tests for FraudProtectionDispatcher.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher
 */
class FraudProtectionDispatcherTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private $sut;

	/**
	 * Mock API client.
	 *
	 * @var ApiClient|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $api_client_mock;

	/**
	 * Mock decision handler.
	 *
	 * @var DecisionHandler|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $decision_handler_mock;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $data_collector_mock;

	/**
	 * Mock session clearance manager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $session_manager_mock;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mocks.
		$this->api_client_mock       = $this->createMock( ApiClient::class );
		$this->decision_handler_mock = $this->createMock( DecisionHandler::class );
		$this->data_collector_mock   = $this->createMock( SessionDataCollector::class );
		$this->session_manager_mock  = $this->createMock( SessionClearanceManager::class );

		// Create dispatcher and inject mocks.
		$this->sut = new FraudProtectionDispatcher();
		$this->sut->init(
			$this->api_client_mock,
			$this->decision_handler_mock,
			$this->data_collector_mock,
			$this->session_manager_mock
		);
	}

	/**
	 * Test that non-checkout events are queued in session, not sent to API.
	 */
	public function test_non_checkout_events_are_queued_in_session(): void {
		$event_type = 'cart_item_added';
		$event_data = array(
			'action'     => 'item_added',
			'product_id' => 123,
		);

		$collected_data = array(
			'session'    => array( 'session_id' => 'test-session-123' ),
			'action'     => 'item_added',
			'product_id' => 123,
		);

		// Expect data collector to be called.
		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->with( $event_type, $event_data )
			->willReturn( $collected_data );

		// Expect event to be queued in session.
		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'queue_event' )
			->with( $event_type, $collected_data );

		// API should NOT be called for non-checkout events.
		$this->api_client_mock
			->expects( $this->never() )
			->method( 'send_event' );

		// Decision handler should NOT be called for non-checkout events.
		$this->decision_handler_mock
			->expects( $this->never() )
			->method( 'apply_decision' );

		$this->sut->dispatch_event( $event_type, $event_data );
	}

	/**
	 * Test that checkout event sends to API with prior events and applies decision.
	 */
	public function test_checkout_event_sends_to_api_with_prior_events(): void {
		$event_data = array(
			'order_id' => 456,
		);

		$collected_data = array(
			'session'  => array( 'session_id' => 'test-session-123' ),
			'order_id' => 456,
		);

		$prior_events = array(
			array(
				'event_type' => 'cart_item_added',
				'timestamp'  => '2024-01-27T10:00:00+00:00',
				'event_data' => array( 'product_id' => 123 ),
			),
		);

		// Expect data collector to be called.
		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->with( 'checkout', $event_data )
			->willReturn( $collected_data );

		// Expect prior events to be retrieved from session.
		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'get_event_queue' )
			->willReturn( $prior_events );

		// Expect API client to be called with checkout event and prior events.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with( 'checkout', $collected_data, $prior_events )
			->willReturn( ApiClient::DECISION_ALLOW );

		// Expect queue to be cleared after successful send.
		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'clear_event_queue' );

		// Expect decision handler to be called with the decision.
		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' )
			->with( ApiClient::DECISION_ALLOW, $collected_data );

		$this->sut->dispatch_event( 'checkout', $event_data );
	}

	/**
	 * Test that checkout event applies block decision.
	 */
	public function test_checkout_event_applies_block_decision(): void {
		$collected_data = array(
			'session' => array( 'session_id' => 'test' ),
		);

		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'get_event_queue' )
			->willReturn( array() );

		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->willReturn( ApiClient::DECISION_BLOCK );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'clear_event_queue' );

		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' )
			->with( ApiClient::DECISION_BLOCK, $collected_data );

		$this->sut->dispatch_event( 'checkout', array() );
	}

	/**
	 * Test that filter is applied to event data before queueing.
	 */
	public function test_filter_is_applied_before_queueing(): void {
		$event_type     = 'cart_item_added';
		$collected_data = array(
			'session' => array( 'session_id' => 'test' ),
			'foo'     => 'bar',
		);

		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		// Add a filter that modifies the data.
		add_filter(
			'woocommerce_fraud_protection_event_data',
			function ( $data, $type ) use ( $event_type ) {
				$this->assertEquals( $event_type, $type );
				$data['filtered'] = true;
				return $data;
			},
			10,
			2
		);

		// Expect session manager to receive the filtered data.
		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'queue_event' )
			->with(
				$event_type,
				$this->callback(
					function ( $data ) {
						$this->assertArrayHasKey( 'filtered', $data );
						$this->assertTrue( $data['filtered'] );
						return true;
					}
				)
			);

		$this->sut->dispatch_event( $event_type, array( 'foo' => 'bar' ) );

		// Clean up filter.
		remove_all_filters( 'woocommerce_fraud_protection_event_data' );
	}

	/**
	 * Test that filter is applied to checkout event data before sending.
	 */
	public function test_filter_is_applied_to_checkout_before_sending(): void {
		$collected_data = array(
			'session' => array( 'session_id' => 'test' ),
			'foo'     => 'bar',
		);

		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'get_event_queue' )
			->willReturn( array() );

		// Add a filter that modifies the data.
		add_filter(
			'woocommerce_fraud_protection_event_data',
			function ( $data, $type ) {
				$this->assertEquals( 'checkout', $type );
				$data['filtered'] = true;
				return $data;
			},
			10,
			2
		);

		// Expect API client to receive the filtered data.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with(
				'checkout',
				$this->callback(
					function ( $data ) {
						$this->assertArrayHasKey( 'filtered', $data );
						$this->assertTrue( $data['filtered'] );
						return true;
					}
				),
				$this->anything()
			)
			->willReturn( ApiClient::DECISION_ALLOW );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'clear_event_queue' );

		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' );

		$this->sut->dispatch_event( 'checkout', array( 'foo' => 'bar' ) );

		// Clean up filter.
		remove_all_filters( 'woocommerce_fraud_protection_event_data' );
	}

	/**
	 * Test that checkout event sends empty prior_events array when no events queued.
	 */
	public function test_checkout_sends_empty_prior_events_when_none_queued(): void {
		$collected_data = array(
			'session' => array( 'session_id' => 'test' ),
		);

		$this->data_collector_mock
			->expects( $this->once() )
			->method( 'collect' )
			->willReturn( $collected_data );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'get_event_queue' )
			->willReturn( array() );

		// Expect API client to be called with empty prior_events array.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with( 'checkout', $collected_data, array() )
			->willReturn( ApiClient::DECISION_ALLOW );

		$this->session_manager_mock
			->expects( $this->once() )
			->method( 'clear_event_queue' );

		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' );

		$this->sut->dispatch_event( 'checkout', array() );
	}
}
