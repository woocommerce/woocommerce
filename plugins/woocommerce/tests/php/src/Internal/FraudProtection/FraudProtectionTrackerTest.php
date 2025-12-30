<?php
/**
 * FraudProtectionTrackerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * Tests for FraudProtectionTracker.
 *
 * @covers \Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionTracker
 */
class FraudProtectionTrackerTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var FraudProtectionTracker
	 */
	private $sut;

	/**
	 * Mock session data collector.
	 *
	 * @var SessionDataCollector|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $mock_data_collector;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock.
		$this->mock_data_collector = $this->createMock( SessionDataCollector::class );

		// Create system under test.
		$this->sut = new FraudProtectionTracker();
		$this->sut->init( $this->mock_data_collector );
	}

	/**
	 * Test track_event collects session data and logs successfully.
	 */
	public function test_track_event_collects_and_logs_successfully(): void {
		$event_type          = 'test_event';
		$event_specific_data = array(
			'action'     => 'test_action',
			'product_id' => 123,
		);

		$session_data = array(
			'session' => array( 'session_id' => 'test-session-123' ),
			'action'  => 'test_action',
		);

		// Mock the data collector to return session data.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( $event_type ),
				$this->equalTo( $event_specific_data )
			)
			->willReturn( $session_data );

		// Call track_event - should not throw.
		$this->sut->track_event( $event_type, $event_specific_data );

		// If we get here without exception, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test track_event handles exceptions gracefully.
	 */
	public function test_track_event_handles_exceptions_gracefully(): void {
		$event_type          = 'test_event';
		$event_specific_data = array( 'action' => 'test_action' );

		// Mock the data collector to throw an exception.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->will( $this->throwException( new \Exception( 'Test exception' ) ) );

		// Call track_event - should handle exception gracefully.
		$this->sut->track_event( $event_type, $event_specific_data );

		// If we get here without the exception propagating, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test track_event passes correct parameters to data collector.
	 */
	public function test_track_event_passes_correct_parameters_to_collector(): void {
		$event_type          = 'cart_item_added';
		$event_specific_data = array(
			'action'     => 'item_added',
			'product_id' => 456,
			'quantity'   => 2,
		);

		// Mock the data collector to verify parameters.
		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->identicalTo( $event_type ),
				$this->identicalTo( $event_specific_data )
			)
			->willReturn( array( 'session' => array( 'session_id' => 'test' ) ) );

		$this->sut->track_event( $event_type, $event_specific_data );
	}

	/**
	 * Test track_event works with empty event data.
	 */
	public function test_track_event_works_with_empty_event_data(): void {
		$event_type          = 'test_event';
		$event_specific_data = array();

		$this->mock_data_collector
			->expects( $this->once() )
			->method( 'collect' )
			->with(
				$this->equalTo( $event_type ),
				$this->equalTo( $event_specific_data )
			)
			->willReturn( array( 'session' => array( 'session_id' => 'test' ) ) );

		$this->sut->track_event( $event_type, $event_specific_data );

		$this->assertTrue( true );
	}
}
