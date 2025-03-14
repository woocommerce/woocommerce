<?php
/**
 * Total payments volume processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\TotalPaymentsVolumeProcessor;
use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as RevenueQuery;

/**
 * class WC_Admin_Tests_RemoteSpecs_RuleProcessors_TotalPaymentsVolumeProcessor
 */
class WC_Admin_Tests_RemoteSpecs_RuleProcessors_TotalPaymentsVolumeProcessor extends WC_Unit_Test_Case {

	/**
	 * Greater than 1000 total payments volume evaluates to false.
	 *
	 * @group fast
	 */
	public function test_total_payments_volume_greater_than_1000_evaluates_to_false() {
		$mocked_query = $this->getMockBuilder( RevenueQuery::class )
		->onlyMethods( array( 'get_data' ) )
		->getMock();

		$mocked_query->expects( $this->once() )
		->method( 'get_data' )
		->willReturn(
			(object) array(
				'totals' => (object) array(
					'total_sales' => 1000,
				),
			)
		);

		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'get_reports_query' )
			->willReturn( $mocked_query );

		$rule = json_decode(
			'{
				"type": "total_payments_value",
				"operation": "<",
				"timeframe": "last_month",
				"value": 1000
			}'
		);

		$result = $mock->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Less than 1000 total payments volume evaluates to true.
	 *
	 * @group fast
	 */
	public function test_total_payments_volume_less_than_1000_evaluates_to_false() {
		$mocked_query = $this->getMockBuilder( RevenueQuery::class )
		->onlyMethods( array( 'get_data' ) )
		->getMock();

		$mocked_query->expects( $this->once() )
		->method( 'get_data' )
		->willReturn(
			(object) array(
				'totals' => (object) array(
					'total_sales' => 999,
				),
			)
		);

		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'get_reports_query' )
			->willReturn( $mocked_query );

		$rule = json_decode(
			'{
				"type": "total_payments_value",
				"operation": "<",
				"timeframe": "last_month",
				"value": 1000
			}'
		);

		$result = $mock->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Invalid report data evaluates to false.
	 *
	 * @group fast
	 */
	public function test_invalid_report_data_evaluates_to_false() {
		$mocked_query = $this->getMockBuilder( RevenueQuery::class )
		->onlyMethods( array( 'get_data' ) )
		->getMock();

		$mocked_query->expects( $this->once() )
		->method( 'get_data' )
		->willReturn(
			(object) array()
		);

		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'get_reports_query' )
			->willReturn( $mocked_query );

		$rule = json_decode(
			'{
				"type": "total_payments_value",
				"operation": "<",
				"timeframe": "last_month",
				"value": 1000
			}'
		);

		$result = $mock->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Test validation with invalid data.
	 */
	public function test_validate_invalid_data() {
		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		$this->assertFalse( $mock->validate( (object) array() ) );
		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
				)
			)
		);

		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'invalid',
					'value'     => 100,
					'operation' => '=',
				)
			)
		);

		// Test invalid range operation cases.
		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => 100, // Should be array for range.
					'operation' => 'range',
				)
			)
		);
		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 100 ), // Array too short.
					'operation' => 'range',
				)
			)
		);
		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 100, 200, 300 ), // Array too long.
					'operation' => 'range',
				)
			)
		);
		$this->assertFalse(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 'invalid', 200 ), // Non-numeric values.
					'operation' => 'range',
				)
			)
		);
	}

	/**
	 * Test validation with valid data.
	 */
	public function test_validate_valid_data() {
		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		// Test regular comparison operation.
		$this->assertTrue(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => 100,
					'operation' => '=',
				)
			)
		);

		// Test range operation.
		$this->assertTrue(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 0, 5000000 ),
					'operation' => 'range',
				)
			)
		);

		// Test range operation with decimal values.
		$this->assertTrue(
			$mock->validate(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 0.5, 5000000.50 ),
					'operation' => 'range',
				)
			)
		);
	}

	/**
	 * Test process with range operation.
	 */
	public function test_process_range_operation() {
		$mock = $this->getMockBuilder( TotalPaymentsVolumeProcessor::class )
			->onlyMethods( array( 'get_reports_query' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'get_reports_query' )
			->willReturn(
				new class() {
					/**
					 * Get the report data.
					 *
					 * @return object The report data.
					 */
					public function get_data() {
						return (object) array(
							'totals' => (object) array(
								'total_sales' => 3000000,
							),
						);
					}
				}
			);

		$this->assertTrue(
			$mock->process(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 0, 5000000 ),
					'operation' => 'range',
				),
				(object) array()
			)
		);
	}
}
