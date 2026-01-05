<?php
/**
 * EventTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\EventTracker;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;
use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for EventTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\EventTracker
 */
class EventTrackerTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The system under test.
	 *
	 * @var EventTracker
	 */
	private $sut;

	/**
	 * Mock FraudProtectionController.
	 *
	 * @var FraudProtectionController|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_controller;

	/**
	 * Mock ApiClient.
	 *
	 * @var ApiClient|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_api_client;

	/**
	 * Mock SessionClearanceManager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_session_manager;

	/**
	 * Mock SessionDataCollector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_data_collector;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mocks for dependencies.
		$this->mock_controller       = $this->createMock( FraudProtectionController::class );
		$this->mock_api_client       = $this->createMock( ApiClient::class );
		$this->mock_session_manager  = $this->createMock( SessionClearanceManager::class );
		$this->mock_data_collector   = $this->createMock( SessionDataCollector::class );

		// Create system under test.
		$this->sut = new EventTracker();
		$this->sut->init(
			$this->mock_controller,
			$this->mock_api_client,
			$this->mock_session_manager,
			$this->mock_data_collector
		);
	}

	/**
	 * @testdox register() should add action hook for tracking events
	 */
	public function test_register_adds_action_hook(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_action( 'woocommerce_fraud_protection_track_event', array( $this->sut, 'on_track_event' ) ),
			'Should register action hook for event tracking'
		);
	}

	/**
	 * @testdox track_event() should skip tracking when feature flag is disabled
	 */
	public function test_track_event_skips_when_feature_disabled(): void {
		// Setup: Feature flag disabled.
		$this->mock_controller->expects( $this->once() )
			->method( 'feature_is_enabled' )
			->willReturn( false );

		// Expect: No other methods called.
		$this->mock_session_manager->expects( $this->never() )->method( 'is_session_allowed' );
		$this->mock_data_collector->expects( $this->never() )->method( 'collect' );
		$this->mock_api_client->expects( $this->never() )->method( 'track_event' );

		// Execute.
		$result = $this->sut->track_event( 'test_event', array() );

		// Verify: Returns allow.
		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * @testdox track_event() should skip tracking when session is whitelisted
	 */
	public function test_track_event_skips_when_session_whitelisted(): void {
		// Setup: Feature enabled, session whitelisted.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->expects( $this->once() )
			->method( 'is_session_allowed' )
			->willReturn( true );
		$this->mock_session_manager->method( 'get_session_id' )->willReturn( 'test-session-id' );

		// Expect: No data collection or API calls.
		$this->mock_data_collector->expects( $this->never() )->method( 'collect' );
		$this->mock_api_client->expects( $this->never() )->method( 'track_event' );

		// Execute.
		$result = $this->sut->track_event( 'test_event', array() );

		// Verify: Returns allow.
		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * @testdox track_event() should collect data and call API when checks pass
	 */
	public function test_track_event_calls_api_when_checks_pass(): void {
		// Setup: Feature enabled, session not whitelisted.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		// Setup: Mock collected data.
		$collected_data = array(
			'session'          => array(
				'session_id'      => 'test-session',
				'ip_address'      => '192.168.1.1',
				'email'           => 'test@example.com',
				'ja3_hash'        => null,
				'user_agent'      => 'Mozilla/5.0',
				'is_user_session' => true,
			),
			'customer'         => array(
				'first_name'           => 'John',
				'last_name'            => 'Doe',
				'billing_email'        => 'test@example.com',
				'lifetime_order_count' => 0,
			),
			'order'            => array(
				'customer_id' => 'guest',
				'cart_hash'   => 'abc123',
			),
			'billing_address'  => array( 'country' => 'US' ),
			'shipping_address' => array(),
			'payment'          => array(
				'payment_method_type' => 'card',
				'card_bin'            => '424242',
				'card_last4'          => '4242',
			),
		);

		$this->mock_data_collector->expects( $this->once() )
			->method( 'collect' )
			->with( 'test_event', array() )
			->willReturn( $collected_data );

		// Expect: API called with flattened data.
		$this->mock_api_client->expects( $this->once() )
			->method( 'track_event' )
			->with(
				'test_event',
				$this->callback(
					function ( $data ) {
						// Verify flattened structure.
						$this->assertArrayHasKey( 'session_id', $data );
						$this->assertArrayHasKey( 'ip_address', $data );
						$this->assertArrayHasKey( 'email', $data );
						$this->assertArrayHasKey( 'email_normalized', $data );
						$this->assertArrayHasKey( 'customer_id', $data );
						$this->assertArrayHasKey( 'cart_hash', $data );
						$this->assertArrayHasKey( 'card_bin', $data );
						return true;
					}
				)
			)
			->willReturn( ApiClient::DECISION_ALLOW );

		// Expect: Session status updated based on verdict.
		$this->mock_session_manager->expects( $this->once() )
			->method( 'allow_session' );

		// Execute.
		$result = $this->sut->track_event( 'test_event', array() );

		// Verify: Returns verdict from API.
		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * @testdox track_event() should update session to allowed when API returns allow verdict
	 */
	public function test_track_event_updates_session_to_allowed_on_allow_verdict(): void {
		// Setup.
		$this->setup_basic_mocks();

		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_ALLOW );

		// Expect: allow_session called.
		$this->mock_session_manager->expects( $this->once() )->method( 'allow_session' );
		$this->mock_session_manager->expects( $this->never() )->method( 'challenge_session' );
		$this->mock_session_manager->expects( $this->never() )->method( 'block_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );
	}

	/**
	 * @testdox track_event() should update session to challenged when API returns challenge verdict
	 */
	public function test_track_event_updates_session_to_challenged_on_challenge_verdict(): void {
		// Setup.
		$this->setup_basic_mocks();

		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_CHALLENGE );

		// Expect: challenge_session called.
		$this->mock_session_manager->expects( $this->never() )->method( 'allow_session' );
		$this->mock_session_manager->expects( $this->once() )->method( 'challenge_session' );
		$this->mock_session_manager->expects( $this->never() )->method( 'block_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );
	}

	/**
	 * @testdox track_event() should update session to blocked when API returns block verdict
	 */
	public function test_track_event_updates_session_to_blocked_on_block_verdict(): void {
		// Setup.
		$this->setup_basic_mocks();

		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_BLOCK );

		// Expect: block_session called.
		$this->mock_session_manager->expects( $this->never() )->method( 'allow_session' );
		$this->mock_session_manager->expects( $this->never() )->method( 'challenge_session' );
		$this->mock_session_manager->expects( $this->once() )->method( 'block_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );
	}

	/**
	 * @testdox track_event() should allow session when API returns unknown verdict
	 */
	public function test_track_event_allows_session_on_unknown_verdict(): void {
		// Setup.
		$this->setup_basic_mocks();

		$this->mock_api_client->method( 'track_event' )->willReturn( 'unknown_verdict' );

		// Expect: allow_session called (fail-safe).
		$this->mock_session_manager->expects( $this->once() )->method( 'allow_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );
	}

	/**
	 * @testdox track_event() should apply extension filters before sending to API
	 */
	public function test_track_event_applies_extension_filters(): void {
		// Setup.
		$this->setup_basic_mocks();

		// Add filter to modify collected data.
		$filter_called = false;
		add_filter(
			'woocommerce_fraud_protection_event_data',
			function ( $data, $event_type ) use ( &$filter_called ) {
				$filter_called        = true;
				$data['test_key']     = 'test_value';
				$data['event_type']   = $event_type;
				return $data;
			},
			10,
			2
		);

		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_ALLOW );
		$this->mock_session_manager->method( 'allow_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );

		// Verify: Filter was called.
		$this->assertTrue( $filter_called, 'Extension filter should be called' );

		// Cleanup.
		remove_all_filters( 'woocommerce_fraud_protection_event_data' );
	}

	/**
	 * @testdox track_event() should apply event-specific extension filters
	 */
	public function test_track_event_applies_event_specific_filters(): void {
		// Setup.
		$this->setup_basic_mocks();

		// Add event-specific filter.
		$specific_filter_called = false;
		add_filter(
			'woocommerce_fraud_protection_event_data_cart_item_added',
			function ( $data ) use ( &$specific_filter_called ) {
				$specific_filter_called = true;
				$data['specific']       = 'value';
				return $data;
			}
		);

		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_ALLOW );
		$this->mock_session_manager->method( 'allow_session' );

		// Execute.
		$this->sut->track_event( 'cart_item_added', array() );

		// Verify: Event-specific filter was called.
		$this->assertTrue( $specific_filter_called, 'Event-specific filter should be called' );

		// Cleanup.
		remove_all_filters( 'woocommerce_fraud_protection_event_data_cart_item_added' );
	}

	/**
	 * @testdox track_event() should normalize email addresses correctly
	 */
	public function test_track_event_normalizes_email_addresses(): void {
		// Setup.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		// Setup collected data with email containing +alias.
		$collected_data = array(
			'session'         => array(
				'session_id' => 'test',
				'email'      => 'User+Test@Example.COM',
			),
			'customer'        => array(),
			'order'           => array(),
			'billing_address' => array(),
			'payment'         => array(),
		);

		$this->mock_data_collector->method( 'collect' )->willReturn( $collected_data );

		// Capture flattened data sent to API.
		$captured_data = null;
		$this->mock_api_client->expects( $this->once() )
			->method( 'track_event' )
			->willReturnCallback(
				function ( $event_type, $data ) use ( &$captured_data ) {
					$captured_data = $data;
					return ApiClient::DECISION_ALLOW;
				}
			);

		$this->mock_session_manager->method( 'allow_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );

		// Verify: Email normalized (lowercase, +alias removed).
		$this->assertSame( 'User+Test@Example.COM', $captured_data['email'] );
		$this->assertSame( 'user@example.com', $captured_data['email_normalized'] );
	}

	/**
	 * @testdox track_event() should handle exceptions gracefully and return allow verdict
	 */
	public function test_track_event_handles_exceptions_gracefully(): void {
		// Setup: Feature enabled.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		// Setup: Data collector throws exception.
		$this->mock_data_collector->method( 'collect' )
			->willThrowException( new \Exception( 'Test exception' ) );

		// Execute.
		$result = $this->sut->track_event( 'test_event', array() );

		// Verify: Returns allow (fail-safe).
		$this->assertSame( ApiClient::DECISION_ALLOW, $result );

		// Verify: Error logged.
		$this->assertLogged( 'error', 'Failed to track fraud protection event: test_event', array( 'source' => 'woo-fraud-protection' ) );
	}

	/**
	 * @testdox track_event() should flatten nested data structure correctly
	 */
	public function test_track_event_flattens_nested_data_correctly(): void {
		// Setup.
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		// Complex nested structure.
		$collected_data = array(
			'session'          => array(
				'session_id' => 'sess-123',
				'ip_address' => '10.0.0.1',
				'email'      => 'test@example.com',
				'ja3_hash'   => 'hash123',
				'user_agent' => 'TestAgent/1.0',
			),
			'customer'         => array(
				'billing_email' => 'customer@example.com',
			),
			'order'            => array(
				'customer_id' => 'cust-456',
				'cart_hash'   => 'cart-abc',
			),
			'billing_address'  => array(
				'country' => 'UK',
			),
			'shipping_address' => array(),
			'payment'          => array(
				'payment_method_type'       => 'card',
				'card_bin'                  => '123456',
				'card_last4'                => '7890',
				'card_brand'                => 'visa',
				'tokenized_card_identifier' => 'token-xyz',
			),
		);

		$this->mock_data_collector->method( 'collect' )->willReturn( $collected_data );

		// Capture flattened data.
		$captured_data = null;
		$this->mock_api_client->expects( $this->once() )
			->method( 'track_event' )
			->willReturnCallback(
				function ( $event_type, $data ) use ( &$captured_data ) {
					$captured_data = $data;
					return ApiClient::DECISION_ALLOW;
				}
			);

		$this->mock_session_manager->method( 'allow_session' );

		// Execute.
		$this->sut->track_event( 'test_event', array() );

		// Verify: Data is flattened correctly.
		$this->assertSame( 'sess-123', $captured_data['session_id'] );
		$this->assertSame( '10.0.0.1', $captured_data['ip_address'] );
		$this->assertSame( 'test@example.com', $captured_data['email'] );
		$this->assertSame( 'hash123', $captured_data['ja3_hash'] );
		$this->assertSame( 'TestAgent/1.0', $captured_data['user_agent'] );
		$this->assertSame( 'cust-456', $captured_data['customer_id'] );
		$this->assertSame( 'UK', $captured_data['billing_country'] );
		$this->assertSame( 'cart-abc', $captured_data['cart_hash'] );
		$this->assertSame( 'card', $captured_data['payment_method_type'] );
		$this->assertSame( '123456', $captured_data['card_bin'] );
		$this->assertSame( '7890', $captured_data['card_last4'] );
		$this->assertSame( 'visa', $captured_data['card_brand'] );
		$this->assertSame( 'token-xyz', $captured_data['tokenized_card_identifier'] );
	}

	/**
	 * @testdox on_track_event() should call track_event with provided parameters
	 */
	public function test_on_track_event_calls_track_event(): void {
		// Setup basic mocks.
		$this->setup_basic_mocks();
		$this->mock_api_client->method( 'track_event' )->willReturn( ApiClient::DECISION_ALLOW );
		$this->mock_session_manager->method( 'allow_session' );

		// Execute: Call public hook handler.
		$this->sut->on_track_event( 'test_event', array( 'test_data' => 'value' ) );
	}

	/**
	 * Helper method to setup basic mocks for common test scenarios.
	 */
	private function setup_basic_mocks(): void {
		$this->mock_controller->method( 'feature_is_enabled' )->willReturn( true );
		$this->mock_session_manager->method( 'is_session_allowed' )->willReturn( false );

		$this->mock_data_collector->method( 'collect' )->willReturn(
			array(
				'session'          => array( 'session_id' => 'test' ),
				'customer'         => array(),
				'order'            => array(),
				'billing_address'  => array(),
				'shipping_address' => array(),
				'payment'          => array(),
			)
		);
	}
}
