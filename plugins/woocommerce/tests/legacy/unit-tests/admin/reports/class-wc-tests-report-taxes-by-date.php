<?php
/**
 * Class WC_Tests_Report_Taxes_By_Date file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

declare(strict_types=1);

/**
 * Tests for the WC_Report_Taxes_By_Date class.
 */
class WC_Tests_Report_Taxes_By_Date extends WC_Unit_Test_Case {

	/**
	 * Load the necessary files including the mock subclass.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-report-taxes-by-date.php';

		// Mock must be included after the parent class is loaded so the class declaration succeeds.
		include_once __DIR__ . '/class-wc-report-taxes-by-date-mock.php';
	}

	/**
	 * Test that partial refunds occurring on dates without orders do not trigger fatal error,
	 * and that the refund row values are reflected in the rendered table.
	 */
	public function test_output_report_with_partial_refund_on_uninitialized_date(): void {
		$report                = new WC_Report_Taxes_By_Date_Mock();
		$report->chart_groupby = 'day';

		// Call 0: $tax_rows_orders — order on 2026-09-01.
		$order_row = (object) array(
			'post_date'           => '2026-09-01 10:00:00',
			'total_orders'        => 1,
			'tax_amount'          => 10.0,
			'shipping_tax_amount' => 2.0,
			'total_sales'         => 100.0,
			'total_shipping'      => 10.0,
		);

		// Call 1: $tax_rows_full_refunds — none.
		$full_refunds = array();

		// Call 2: $tax_rows_partial_refunds — partial refund on 2026-09-02 (no order on that date).
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

		// Report must produce HTML output without fatal errors.
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( '<table class="widefat">', $output );

		// The order date (2026-09-01) and refund date (2026-09-02) must both appear in the output,
		// formatted exactly as output_report() renders them via date_i18n().
		$this->assertStringContainsString( date_i18n( get_option( 'date_format' ), strtotime( '2026-09-01' ) ), $output );
		$this->assertStringContainsString( date_i18n( get_option( 'date_format' ), strtotime( '2026-09-02' ) ), $output );

		// Refund tax amount (−5) must appear, confirming the refund row was processed.
		$this->assertStringContainsString( wc_price( -5.0 ), $output );
	}
}
