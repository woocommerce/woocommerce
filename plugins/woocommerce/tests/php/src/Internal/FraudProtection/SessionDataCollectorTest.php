<?php
/**
 * SessionDataCollectorTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * Tests for SessionDataCollector.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector
 */
class SessionDataCollectorTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var SessionDataCollector
	 */
	private $sut;

	/**
	 * SessionClearanceManager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_clearance_manager;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure WooCommerce cart and session are available.
		if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$this->session_clearance_manager = new SessionClearanceManager();
		$this->sut                        = new SessionDataCollector( $this->session_clearance_manager );
	}

	/**
	 * Test that collect() method returns properly structured nested array with 9 top-level keys.
	 */
	public function test_collect_returns_properly_structured_nested_array() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'event_type', $result );
		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertArrayHasKey( 'session', $result );
		$this->assertArrayHasKey( 'customer', $result );
		$this->assertArrayHasKey( 'order', $result );
		$this->assertArrayHasKey( 'shipping_address', $result );
		$this->assertArrayHasKey( 'billing_address', $result );
		$this->assertArrayHasKey( 'payment', $result );
		$this->assertArrayHasKey( 'event_data', $result );
		$this->assertCount( 9, $result );
	}

	/**
	 * Test that collect() accepts event_type and event_data parameters.
	 */
	public function test_collect_accepts_event_type_and_event_data_parameters() {
		$event_type = 'checkout_started';
		$event_data = array(
			'page'   => 'checkout',
			'source' => 'test',
		);

		$result = $this->sut->collect( $event_type, $event_data );

		$this->assertEquals( $event_type, $result['event_type'] );
		$this->assertEquals( $event_data, $result['event_data'] );
	}

	/**
	 * Test graceful degradation when session is unavailable.
	 */
	public function test_graceful_degradation_when_session_unavailable() {
		// This test verifies that collect() doesn't throw exceptions even if session is unavailable.
		// We can't easily simulate session being unavailable in unit tests without mocking,
		// but we can verify that calling collect() returns a valid structure.
		$result = $this->sut->collect();

		$this->assertIsArray( $result );
		$this->assertCount( 9, $result );
		// All sections should be initialized even if session unavailable.
		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
	}

	/**
	 * Test timestamp format is UTC (gmdate format).
	 */
	public function test_timestamp_format_is_utc() {
		$result = $this->sut->collect();

		$this->assertArrayHasKey( 'timestamp', $result );
		$this->assertNotEmpty( $result['timestamp'] );

		// Verify timestamp is in Y-m-d H:i:s format.
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result['timestamp'] );

		// Verify timestamp is recent (within last 10 seconds).
		$timestamp      = strtotime( $result['timestamp'] );
		$current_time   = time();
		$time_difference = abs( $current_time - $timestamp );
		$this->assertLessThanOrEqual( 10, $time_difference, 'Timestamp should be recent (within 10 seconds)' );
	}

	/**
	 * Test that collect() uses default values when parameters not provided.
	 */
	public function test_collect_uses_default_values_when_parameters_not_provided() {
		$result = $this->sut->collect();

		$this->assertNull( $result['event_type'] );
		$this->assertEquals( array(), $result['event_data'] );
	}

	/**
	 * Test that nested sections are initialized as arrays.
	 */
	public function test_nested_sections_initialized_as_arrays() {
		$result = $this->sut->collect();

		$this->assertIsArray( $result['session'] );
		$this->assertIsArray( $result['customer'] );
		$this->assertIsArray( $result['order'] );
		$this->assertIsArray( $result['shipping_address'] );
		$this->assertIsArray( $result['billing_address'] );
		$this->assertIsArray( $result['payment'] );
	}
}
