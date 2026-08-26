<?php
/**
 * Class WC_Tests_Report_Sales_By_Date file.
 *
 * @package WooCommerce\Tests\Admin\Reports
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the WC_Report_Sales_By_Date class.
 */
class WC_Tests_Report_Sales_By_Date extends WC_Unit_Test_Case {

	/**
	 * Thousands separator to restore after a test overrides it.
	 *
	 * @var string|null
	 */
	private $original_thousands_sep = null;

	/**
	 * Counts to give the report through the woocommerce_admin_report_data filter.
	 *
	 * @var array
	 */
	private $report_counts = array();

	/**
	 * Load the necessary files, as they're not automatically loaded by WooCommerce.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-admin-report.php';
		include_once WC_Unit_Tests_Bootstrap::instance()->plugin_dir . '/includes/admin/reports/class-wc-report-sales-by-date.php';
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		global $wp_locale;

		if ( null !== $this->original_thousands_sep ) {
			$wp_locale->number_format['thousands_sep'] = $this->original_thousands_sep;
			$this->original_thousands_sep              = null;
		}

		parent::tearDown();
	}

	/**
	 * Clear cached report data.
	 *
	 * @before
	 */
	public function clear_transients() {
		delete_transient( 'wc_report_sales_by_date' );
	}

	/**
	 * Test: get_report_data
	 */
	public function test_get_report_data() {
		if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'This test is not compatible with the custom orders table.' );
		}

		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon();
		$tax     = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		// A standard order.
		$order1 = WC_Helper_Order::create_order( 0, $product->get_id() );
		$order1->set_status( OrderStatus::COMPLETED );
		$order1->save();

		// An order using a coupon.
		$order2 = WC_Helper_Order::create_order();
		$order2->apply_coupon( $coupon );
		$order2->set_status( OrderStatus::COMPLETED );
		$order2->save();

		// An order that was refunded, save for shipping.
		$order3 = WC_Helper_Order::create_order();
		$order3->set_status( OrderStatus::COMPLETED );
		$order3->save();
		wc_create_refund(
			array(
				'amount'   => 7,
				'order_id' => $order3->get_id(),
			)
		);

		// Parameters borrowed from WC_Admin_Reports::replace_dashboard_status_widget_reports().
		$report                 = new WC_Report_Sales_By_Date();
		$report->start_date     = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
		$report->end_date       = current_time( 'timestamp' );
		$report->chart_groupby  = 'day';
		$report->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
		$data                   = $report->get_report_data();

		$this->assertEquals( 3, $data->order_counts[0]->count, 'Expected to see three orders in total.' );
		$this->assertEquals( $data->order_counts[0]->count, $data->total_orders );

		$this->assertEquals(
			$order1->get_item_count() + $order2->get_item_count() + $order3->get_item_count(),
			$data->order_items[0]->order_item_count,
			'Order item count.'
		);
		$this->assertEquals( $data->order_items[0]->order_item_count, $data->total_items, 'Total items.' );

		$this->assertEquals(
			$coupon->get_code(),
			$data->coupons[0]->order_item_name,
			'There should be a single coupon applied.'
		);
		$this->assertEquals( wc_format_decimal( $data->coupons[0]->discount_amount, '' ), $data->total_coupons, 'Total discount amount.' );

		$this->assertCount( 1, $data->refund_lines, 'There was one refund granted.' );
		$this->assertEquals( 7, $data->partial_refunds[0]->total_refund, 'Total refunds.' );
		$this->assertEquals( $data->partial_refunds[0]->total_refund, $data->total_refunds, 'Day refunds, total refunds.' );

		$this->assertEquals(
			$order1->get_shipping_total() + $order2->get_shipping_total() + $order3->get_shipping_total(),
			$data->orders[0]->total_shipping,
			'Orders, total shipping.'
		);
		$this->assertEquals( wc_format_decimal( $data->orders[0]->total_shipping, '' ), $data->total_shipping, 'Day shipping, total shipping.' );

		$this->assertEquals(
			$order1->get_shipping_tax() + $order2->get_shipping_tax() + $order3->get_shipping_tax(),
			$data->orders[0]->total_shipping_tax,
			'Orders, total shipping tax.'
		);
		$this->assertEquals( wc_format_decimal( $data->orders[0]->total_shipping_tax, '' ), $data->total_shipping_tax, 'Day shipping tax, total shipping tax.' );

		$this->assertEquals( wc_format_decimal( $data->orders[0]->total_tax, '' ), $data->total_tax, 'Day tax, total tax.' );

		$this->assertEquals(
			$order1->get_total() + $order2->get_total() + $order3->get_total(),
			$data->orders[0]->total_sales,
			'Orders, total sales.'
		);
		$this->assertEquals(
			wc_format_decimal( $data->orders[0]->total_sales - $data->total_refunds, '' ),
			$data->total_sales,
			'Day sales, total sales.'
		);
		$this->assertEquals( $data->total_sales, $data->average_total_sales, 'Average total sales.' );

		$this->assertEquals(
			$order1->get_subtotal() + $order2->get_subtotal() + $order3->get_subtotal() - $data->total_refunds - $data->total_coupons,
			$data->net_sales,
			'Net sales.'
		);
		$this->assertEquals( $data->net_sales, $data->average_sales, 'For this window, Average sales should match net sales.' );

		// Report columns that won't have data, but we want to ensure they exist.
		$this->assertEmpty( $data->full_refunds );
		$this->assertEquals( 0, $data->refunded_order_items );
		$this->assertEquals( 0, $data->total_tax_refunded );
		$this->assertEquals( 0, $data->total_shipping_refunded );
		$this->assertEquals( 0, $data->total_shipping_tax_refunded );
		$this->assertEquals( 0, $data->total_refunded_orders );
	}

	/**
	 * @testdox Should format chart legend counts with the locale thousands separator.
	 *
	 * @dataProvider provide_legend_counts
	 *
	 * @param array    $counts   Counts to give the report.
	 * @param string[] $expected Strings the legend should contain.
	 */
	public function test_get_chart_legend_formats_counts_with_locale_separator( $counts, $expected ) {
		global $wp_locale;

		$this->original_thousands_sep              = $wp_locale->number_format['thousands_sep'];
		$wp_locale->number_format['thousands_sep'] = '.';

		$this->report_counts = $counts;
		add_filter( 'woocommerce_admin_report_data', array( $this, 'set_report_counts' ) );

		$report                 = new WC_Report_Sales_By_Date();
		$report->chart_colours  = array_fill_keys(
			array( 'sales_amount', 'average', 'net_sales_amount', 'net_average', 'order_count', 'item_count', 'refund_amount', 'shipping_amount', 'coupon_amount' ),
			'#000000'
		);
		$report->start_date     = strtotime( '-1 month' );
		$report->end_date       = time();
		$report->chart_groupby  = 'day';
		$report->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';

		$legend = implode( ' ', wp_list_pluck( $report->get_chart_legend(), 'title' ) );

		foreach ( $expected as $needle ) {
			$this->assertStringContainsString( $needle, $legend, 'Legend counts should use the locale thousands separator.' );
		}
	}

	/**
	 * Counts to render the legend with, and the strings they should produce.
	 *
	 * The refund line is a _n() string, so it needs a case for each form.
	 *
	 * @return array[]
	 */
	public function provide_legend_counts() {
		return array(
			'plural refunds'  => array(
				array(
					'total_orders'          => 12345,
					'total_items'           => 67890,
					'total_refunded_orders' => 1234,
					'refunded_order_items'  => 5678,
				),
				array(
					'<strong>12.345</strong> orders placed',
					'<strong>67.890</strong> items purchased',
					'refunded 1.234 orders (5.678 items)',
				),
			),
			'singular refund' => array(
				array(
					'total_orders'          => 1000,
					'total_items'           => 2000,
					'total_refunded_orders' => 1,
					'refunded_order_items'  => 1500,
				),
				array(
					'<strong>1.000</strong> orders placed',
					'<strong>2.000</strong> items purchased',
					'refunded 1 order (1.500 item)',
				),
			),
		);
	}

	/**
	 * Give the report the counts the running test needs.
	 *
	 * @param stdClass $data Report data.
	 * @return stdClass
	 */
	public function set_report_counts( $data ) {
		foreach ( $this->report_counts as $key => $value ) {
			$data->$key = $value;
		}

		return $data;
	}
}
