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
	 * Test validation with invalid data using a data provider.
	 *
	 * @param array  $rule    The rule to validate.
	 * @param string $message The message to display.
	 *
	 * @dataProvider data_provider_invalid_data
	 */
	public function test_validate_invalid_data( $rule, $message ) {
		$processor = new TotalPaymentsVolumeProcessor();
		$this->assertFalse( $processor->validate( (object) $rule ), $message );
	}

	/**
	 * Data provider for invalid data validation tests.
	 *
	 * @return array
	 */
	public function data_provider_invalid_data() {
		return array(
			'empty_rule'                              => array(
				array(),
				'Validation should fail for an empty rule.',
			),
			'missing_value_and_operation'             => array(
				array(
					'timeframe' => 'last_week',
				),
				'Validation should fail when value and operation are missing.',
			),
			'invalid_timeframe'                       => array(
				array(
					'timeframe' => 'invalid',
					'value'     => 100,
					'operation' => '=',
				),
				'Validation should fail for an invalid timeframe.',
			),
			'range_operation_with_non_array_value'    => array(
				array(
					'timeframe' => 'last_week',
					'value'     => 100,
					'operation' => 'range',
				),
				'Validation should fail when range operation is used with non-array value.',
			),
			'range_operation_with_short_array'        => array(
				array(
					'timeframe' => 'last_week',
					'value'     => array( 100 ),
					'operation' => 'range',
				),
				'Validation should fail when range operation is used with an array that is too short.',
			),
			'range_operation_with_long_array'         => array(
				array(
					'timeframe' => 'last_week',
					'value'     => array( 100, 200, 300 ),
					'operation' => 'range',
				),
				'Validation should fail when range operation is used with an array that is too long.',
			),
			'range_operation_with_non_numeric_values' => array(
				array(
					'timeframe' => 'last_week',
					'value'     => array( 'invalid', 200 ),
					'operation' => 'range',
				),
				'Validation should fail when range operation is used with non-numeric values.',
			),
		);
	}

	/**
	 * Test validation with valid data.
	 *
	 * @param object $rule    The rule to validate.
	 * @param string $message The message to display.
	 *
	 * @dataProvider data_provider_valid_data
	 */
	public function test_validate_valid_data( $rule, $message ) {
		$processor = new TotalPaymentsVolumeProcessor();
		$this->assertTrue( $processor->validate( $rule ), $message );
	}

	/**
	 * Data provider for test_validate_valid_data.
	 *
	 * @return array
	 */
	public function data_provider_valid_data() {
		return array(
			'regular_comparison_operation'        => array(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => 100,
					'operation' => '=',
				),
				'Validation should pass for regular comparison operation.',
			),
			'range_operation'                     => array(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 0, 5000000 ),
					'operation' => 'range',
				),
				'Validation should pass for range operation with integer values.',
			),
			'range_operation_with_decimal_values' => array(
				(object) array(
					'timeframe' => 'last_week',
					'value'     => array( 0.5, 5000000.50 ),
					'operation' => 'range',
				),
				'Validation should pass for range operation with decimal values.',
			),
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
