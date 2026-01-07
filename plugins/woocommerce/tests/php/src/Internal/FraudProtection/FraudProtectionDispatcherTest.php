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
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;

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
	 * Mock fraud protection controller.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $controller_mock;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mocks.
		$this->api_client_mock       = $this->createMock( ApiClient::class );
		$this->decision_handler_mock = $this->createMock( DecisionHandler::class );
		$this->controller_mock       = $this->createMock( FraudProtectionController::class );

		// By default, feature is enabled.
		$this->controller_mock->method( 'feature_is_enabled' )->willReturn( true );

		// Create dispatcher and inject mocks.
		$this->sut = new FraudProtectionDispatcher();
		$this->sut->init(
			$this->api_client_mock,
			$this->decision_handler_mock,
			$this->controller_mock
		);
	}

	/**
	 * Test that dispatch_event sends event to API and applies decision.
	 */
	public function test_dispatch_event_sends_to_api_and_applies_decision(): void {
		$event_type     = 'test_event';
		$collected_data = array(
			'session'    => array( 'session_id' => 'test-session-123' ),
			'action'     => 'test_action',
			'product_id' => 123,
		);

		// Expect API client to be called with the collected data.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with(
				$this->equalTo( $event_type ),
				$this->callback(
					function ( $data ) use ( $collected_data ) {
						// Verify the payload structure.
						$this->assertArrayHasKey( 'session', $data );
						$this->assertEquals( 'test-session-123', $data['session']['session_id'] );
						$this->assertEquals( 'test_action', $data['action'] );
						$this->assertEquals( 123, $data['product_id'] );
						return true;
					}
				)
			)
			->willReturn( ApiClient::DECISION_ALLOW );

		// Expect decision handler to be called with the decision and session data.
		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' )
			->with( ApiClient::DECISION_ALLOW, array( 'session_id' => 'test-session-123' ) );

		// Call dispatch_event.
		$this->sut->dispatch_event( $event_type, $collected_data );
	}

	/**
	 * Test that dispatch_event handles data without session gracefully.
	 */
	public function test_dispatch_event_handles_missing_session_data(): void {
		$event_type     = 'test_event';
		$collected_data = array( 'invalid' => 'data_without_session' );

		// Expect API client to be called with the collected data.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with(
				$this->equalTo( $event_type ),
				$this->callback(
					function ( $data ) {
						// Verify the payload has the invalid key.
						$this->assertArrayHasKey( 'invalid', $data );
						$this->assertEquals( 'data_without_session', $data['invalid'] );
						// Session key should not exist or be empty.
						$this->assertFalse( isset( $data['session']['session_id'] ) || ! empty( $data['session']['session_id'] ) );
						return true;
					}
				)
			)
			->willReturn( ApiClient::DECISION_ALLOW );

		// Expect decision handler to be called with empty session data.
		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' )
			->with( ApiClient::DECISION_ALLOW, array() );

		// Call dispatch_event - should handle gracefully.
		$this->sut->dispatch_event( $event_type, $collected_data );
	}

	/**
	 * Test that dispatch_event respects block decisions.
	 */
	public function test_dispatch_event_applies_block_decision(): void {
		$event_type     = 'cart_item_added';
		$collected_data = array(
			'session'    => array( 'session_id' => 'test' ),
			'action'     => 'item_added',
			'product_id' => 456,
		);

		// API returns block decision.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with(
				$this->equalTo( $event_type ),
				$this->callback(
					function ( $data ) {
						// Verify the payload structure for cart event.
						$this->assertArrayHasKey( 'session', $data );
						$this->assertEquals( 'test', $data['session']['session_id'] );
						$this->assertEquals( 'item_added', $data['action'] );
						$this->assertEquals( 456, $data['product_id'] );
						return true;
					}
				)
			)
			->willReturn( ApiClient::DECISION_BLOCK );

		// Expect decision handler to be called with block decision.
		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' )
			->with( ApiClient::DECISION_BLOCK, array( 'session_id' => 'test' ) );

		// Call dispatch_event.
		$this->sut->dispatch_event( $event_type, $collected_data );
	}

	/**
	 * Test that dispatch_event doesn't send events when feature is disabled.
	 */
	public function test_dispatch_event_skips_when_feature_disabled(): void {
		// Create fresh API and decision handler mocks that should never be called.
		$api_client_mock = $this->createMock( ApiClient::class );
		$api_client_mock->expects( $this->never() )->method( 'send_event' );

		$decision_handler_mock = $this->createMock( DecisionHandler::class );
		$decision_handler_mock->expects( $this->never() )->method( 'apply_decision' );

		// Create controller mock with feature disabled.
		$controller_mock = $this->createMock( FraudProtectionController::class );
		$controller_mock->expects( $this->once() )
			->method( 'feature_is_enabled' )
			->willReturn( false );

		// Create new dispatcher with feature disabled.
		$sut = new FraudProtectionDispatcher();
		$sut->init( $api_client_mock, $decision_handler_mock, $controller_mock );

		$event_type     = 'test_event';
		$collected_data = array( 'session' => array( 'session_id' => 'test' ) );

		// Call dispatch_event - should bail early without calling API or decision handler.
		$sut->dispatch_event( $event_type, $collected_data );
	}

	/**
	 * Test that dispatch_event applies filter to collected data.
	 */
	public function test_dispatch_event_applies_filter_to_data(): void {
		$event_type     = 'test_event';
		$collected_data = array(
			'session' => array( 'session_id' => 'test' ),
			'foo'     => 'bar',
		);

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

		// Expect API client to receive the filtered data.
		$this->api_client_mock
			->expects( $this->once() )
			->method( 'send_event' )
			->with(
				$this->equalTo( $event_type ),
				$this->callback(
					function ( $data ) {
						// Verify the original data is preserved.
						$this->assertArrayHasKey( 'session', $data );
						$this->assertEquals( 'test', $data['session']['session_id'] );
						$this->assertEquals( 'bar', $data['foo'] );
						// Verify the filter added the 'filtered' key.
						$this->assertArrayHasKey( 'filtered', $data );
						$this->assertTrue( $data['filtered'] );
						return true;
					}
				)
			)
			->willReturn( ApiClient::DECISION_ALLOW );

		$this->decision_handler_mock
			->expects( $this->once() )
			->method( 'apply_decision' );

		// Call dispatch_event.
		$this->sut->dispatch_event( $event_type, $collected_data );

		// Clean up filter.
		remove_all_filters( 'woocommerce_fraud_protection_event_data' );
	}
}
