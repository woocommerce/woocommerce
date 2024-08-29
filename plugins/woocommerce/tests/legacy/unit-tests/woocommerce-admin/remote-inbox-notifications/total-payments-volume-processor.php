<?php
/**
 * Total payments volume processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\TotalPaymentsVolumeProcessor;
use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query as RevenueQuery;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_TotalPaymentsVolumeProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_TotalPaymentsVolumeProcessor extends WC_Unit_Test_Case {

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
}
