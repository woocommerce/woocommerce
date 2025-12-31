<?php
/**
 * FraudProtectionTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;

/**
 * Tests for FraudProtectionTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker
 */
class FraudProtectionTrackerTest extends \WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The system under test.
	 *
	 * @var FraudProtectionTracker
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create system under test.
		$this->sut = new FraudProtectionTracker();
	}

	/**
	 * Test track_event logs collected data successfully.
	 */
	public function test_track_event_logs_collected_data_successfully(): void {
		$event_type     = 'test_event';
		$collected_data = array(
			'session'    => array( 'session_id' => 'test-session-123' ),
			'action'     => 'test_action',
			'product_id' => 123,
		);

		// Call track_event.
		$this->sut->track_event( $event_type, $collected_data );

		// Verify the log was captured.
		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'info', $this->captured_logs[0]['level'] );
		$this->assertStringContainsString( 'test_event', $this->captured_logs[0]['message'] );
		$this->assertStringContainsString( 'test-session-123', $this->captured_logs[0]['message'] );
		$this->assertEquals( 'test_event', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( $collected_data, $this->captured_logs[0]['context']['collected_data'] );
	}

	/**
	 * Test track_event handles data without session gracefully.
	 */
	public function test_track_event_handles_data_without_session_gracefully(): void {
		$event_type     = 'test_event';
		$collected_data = array( 'invalid' => 'data_without_session' );

		// Call track_event with data without session - should handle gracefully and log with N/A.
		$this->sut->track_event( $event_type, $collected_data );

		// Verify the log was captured with N/A for session ID.
		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'info', $this->captured_logs[0]['level'] );
		$this->assertStringContainsString( 'test_event', $this->captured_logs[0]['message'] );
		$this->assertStringContainsString( 'N/A', $this->captured_logs[0]['message'] );
		$this->assertEquals( 'test_event', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( $collected_data, $this->captured_logs[0]['context']['collected_data'] );
	}

	/**
	 * Test track_event accepts various data structures.
	 */
	public function test_track_event_accepts_various_data_structures(): void {
		$event_type     = 'cart_item_added';
		$collected_data = array(
			'session'    => array( 'session_id' => 'test' ),
			'action'     => 'item_added',
			'product_id' => 456,
			'quantity'   => 2,
		);

		// Call track_event.
		$this->sut->track_event( $event_type, $collected_data );

		// Verify the log was captured with all data.
		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'info', $this->captured_logs[0]['level'] );
		$this->assertStringContainsString( 'cart_item_added', $this->captured_logs[0]['message'] );
		$this->assertEquals( 'cart_item_added', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( $collected_data, $this->captured_logs[0]['context']['collected_data'] );
		$this->assertEquals( 'item_added', $this->captured_logs[0]['context']['collected_data']['action'] );
		$this->assertEquals( 456, $this->captured_logs[0]['context']['collected_data']['product_id'] );
		$this->assertEquals( 2, $this->captured_logs[0]['context']['collected_data']['quantity'] );
	}

	/**
	 * Test track_event works with empty collected data.
	 */
	public function test_track_event_works_with_empty_collected_data(): void {
		$event_type     = 'test_event';
		$collected_data = array();

		// Call track_event with empty data.
		$this->sut->track_event( $event_type, $collected_data );

		// Verify the log was captured even with empty data.
		$this->assertCount( 1, $this->captured_logs );
		$this->assertEquals( 'info', $this->captured_logs[0]['level'] );
		$this->assertStringContainsString( 'test_event', $this->captured_logs[0]['message'] );
		$this->assertStringContainsString( 'N/A', $this->captured_logs[0]['message'] );
		$this->assertEquals( 'test_event', $this->captured_logs[0]['context']['event_type'] );
		$this->assertEquals( $collected_data, $this->captured_logs[0]['context']['collected_data'] );
	}
}
