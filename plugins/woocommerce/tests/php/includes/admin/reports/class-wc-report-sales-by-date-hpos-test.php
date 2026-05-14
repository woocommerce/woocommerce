<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * HPOS-aware test coverage for {@see WC_Report_Sales_By_Date}.
 *
 * Verifies the fix for woo#48903 / RSMAPGJ-425: the legacy
 * `wc/v3/reports/sales` REST endpoint feeds off `WC_Report_Sales_By_Date`,
 * which previously queried `wp_posts`/`wp_postmeta` directly and returned
 * empty totals on HPOS-only sites. The HPOS path now sources data via
 * `wc_get_orders()` so the report numbers match the underlying orders.
 */
class WC_Report_Sales_By_Date_HPOS_Test extends WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Load the necessary report files (mirrors the CPT test file's behavior).
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-report-sales-by-date.php';
	}

	/**
	 * Set up: switch on HPOS as the authoritative datastore.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_cot();
	}

	/**
	 * Tear down: restore the default datastore configuration.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		delete_transient( 'wc_report_sales_by_date' );
		parent::tearDown();
	}

	/**
	 * Build a sales report covering the current month for the supplied report instance.
	 */
	private function build_report(): WC_Report_Sales_By_Date {
		$report                 = new WC_Report_Sales_By_Date();
		$report->start_date     = strtotime( gmdate( 'Y-m-01' ) );
		$report->end_date       = time();
		$report->chart_groupby  = 'day';
		$report->chart_interval = 0;
		$report->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';

		return $report;
	}

	/**
	 * Regression: with HPOS as authoritative datastore (no sync to posts), the
	 * legacy sales report must return real totals instead of zeros.
	 */
	public function test_hpos_report_includes_orders_totals(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '50' );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 2 );
		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$data = $this->build_report()->get_report_data();

		$this->assertSame( 1, $data->total_orders, 'HPOS sales report should count the completed order.' );
		$this->assertSame( 2, $data->total_items, 'HPOS sales report should count the line item quantities.' );
		$this->assertGreaterThan( 0, (float) $data->total_sales, 'HPOS sales report total_sales must be non-zero when orders exist.' );
		$this->assertSame(
			wc_format_decimal( (float) $order->get_total(), 2 ),
			$data->total_sales,
			'HPOS sales report total_sales should match the order total.'
		);
	}

	/**
	 * Multiple orders within the range aggregate correctly per day bucket.
	 */
	public function test_hpos_report_aggregates_multiple_orders(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '20' );
		$product->save();

		$totals = 0.0;
		for ( $i = 0; $i < 3; $i++ ) {
			$order = wc_create_order();
			$order->add_product( $product, 1 );
			$order->calculate_totals();
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
			$totals += (float) $order->get_total();
		}

		$data = $this->build_report()->get_report_data();

		$this->assertSame( 3, $data->total_orders );
		$this->assertSame( 3, $data->total_items );
		$this->assertSame( wc_format_decimal( $totals, 2 ), $data->total_sales );
	}

	/**
	 * Orders outside the date range must not affect the totals.
	 */
	public function test_hpos_report_excludes_out_of_range_orders(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10' );
		$product->save();

		// In-range order.
		$in_range_order = wc_create_order();
		$in_range_order->add_product( $product, 1 );
		$in_range_order->calculate_totals();
		$in_range_order->set_status( OrderStatus::COMPLETED );
		$in_range_order->save();

		// Out-of-range order (one year in the past).
		$past_order = wc_create_order();
		$past_order->add_product( $product, 5 );
		$past_order->calculate_totals();
		$past_order->set_status( OrderStatus::COMPLETED );
		$past_order->set_date_created( gmdate( 'Y-m-d H:i:s', strtotime( '-1 year' ) ) );
		$past_order->save();

		$data = $this->build_report()->get_report_data();

		$this->assertSame( 1, $data->total_orders, 'Past order should not be counted in current-month totals.' );
		$this->assertSame( 1, $data->total_items );
		$this->assertSame(
			wc_format_decimal( (float) $in_range_order->get_total(), 2 ),
			$data->total_sales,
			'Only the in-range order should contribute to total_sales.'
		);
	}

	/**
	 * Refunds within range subtract from totals and surface in the refund accumulators.
	 */
	public function test_hpos_report_accounts_for_partial_refund(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '40' );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'amount'   => 7,
				'order_id' => $order->get_id(),
			)
		);

		$data = $this->build_report()->get_report_data();

		$this->assertSame( 1, $data->total_orders, 'The parent order should still be counted.' );
		$this->assertEqualsWithDelta( 7.0, (float) $data->total_refunds, 0.001, 'The partial refund amount should be captured.' );
		$this->assertSame(
			wc_format_decimal( (float) $order->get_total() - 7.0, 2 ),
			$data->total_sales,
			'total_sales should subtract the refunded amount.'
		);
		$this->assertCount( 1, $data->refund_lines, 'A refund line should be recorded.' );
	}
}
