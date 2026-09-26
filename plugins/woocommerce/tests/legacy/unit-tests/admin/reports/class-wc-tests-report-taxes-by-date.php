<?php
/**
 * Class WC_Tests_Report_Taxes_By_Date file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

declare(strict_types=1);

/**
 * Test subclass to inject report data.
 */
class WC_Report_Taxes_By_Date_Mock extends WC_Report_Taxes_By_Date {
	/**
	 * Sequenced return data for get_order_report_data calls.
	 *
	 * @var array
	 */
	public $mock_query_results = array();

	/**
	 * Current call index.
	 *
	 * @var int
	 */
	private $call_index = 0;

	/**
	 * Override get_order_report_data to return mock data.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_order_report_data( $args ) {
		if ( isset( $this->mock_query_results[ $this->call_index ] ) ) {
			$data = $this->mock_query_results[ $this->call_index ];
			++$this->call_index;
			return $data;
		}

		++$this->call_index;
		return array();
	}
}

/**
 * Tests for the WC_Report_Taxes_By_Date class.
 */
class WC_Tests_Report_Taxes_By_Date extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-report-taxes-by-date.php';
	}

	/**
	 * Test that partial refunds occurring on dates without orders do not trigger fatal error.
	 */
	public function test_output_report_with_partial_refund_on_uninitialized_date(): void {
		$report                = new WC_Report_Taxes_By_Date_Mock();
		$report->chart_groupby = 'day';

		// Call 0: $tax_rows_orders - order on 2026-09-01 (index 0).
		$order_row = (object) array(
			'post_date'           => '2026-09-01 10:00:00',
			'total_orders'        => 1,
			'tax_amount'          => 10.0,
			'shipping_tax_amount' => 2.0,
			'total_sales'         => 100.0,
			'total_shipping'      => 10.0,
		);

		// Call 1: $tax_rows_full_refunds - none.
		$full_refunds = array();

		// Call 2: $tax_rows_partial_refunds - partial refund on 2026-09-02 (index 0).
		$partial_refund_row = (object) array(
			'post_date'           => '2026-09-02 12:00:00',
			'total_orders'        => 0,
			'tax_amount'          => -5.0,
			'shipping_tax_amount' => 0.0,
			'total_sales'         => -50.0,
			'total_shipping'      => 0.0,
		);

		$report->mock_query_results = array(
			array( $order_row ),
			$full_refunds,
			array( $partial_refund_row ),
		);

		ob_start();
		$report->output_report();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( '<table class="widefat">', $output );
	}
}
