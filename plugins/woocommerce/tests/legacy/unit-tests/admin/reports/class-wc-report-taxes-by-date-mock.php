<?php
/**
 * Class WC_Report_Taxes_By_Date_Mock file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

if ( ! class_exists( 'WC_Report_Taxes_By_Date', false ) ) {
	require_once dirname( __DIR__, 5 ) . '/includes/admin/reports/class-wc-admin-report.php';
	require_once dirname( __DIR__, 5 ) . '/includes/admin/reports/class-wc-report-taxes-by-date.php';
}

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
