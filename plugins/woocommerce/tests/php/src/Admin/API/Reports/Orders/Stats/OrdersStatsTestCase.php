<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Orders\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use WC_Unit_Test_Case;

/**
 * Base class for the Orders Stats report tests.
 *
 * Disables report caching and provides builders for the expected get_data()
 * result shape, so tests only spell out the values that differ from zero.
 */
abstract class OrdersStatsTestCase extends WC_Unit_Test_Case {

	// Per-product item quantity and per-order shipping hardcoded in WC_Helper_Order::create_order.
	const QTY_PER_PRODUCT = 4;
	const SHIPPING_AMOUNT = 10;

	/**
	 * Don't cache report data during these tests.
	 *
	 * The filter must be added after parent::setUp(): WordPress snapshots the hook
	 * globals per test and restores that snapshot afterwards, which both removes the
	 * filter again and would drop one registered any earlier.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'woocommerce_analytics_report_should_use_cache', '__return_false' );
	}

	/**
	 * Build a totals (or subtotals) block, zero-valued except for the given values.
	 *
	 * Pass a 'products' value for totals-level blocks; interval subtotals omit it.
	 *
	 * @param array $values Non-zero values, keyed like the get_data() totals.
	 * @return array
	 */
	protected function expected_totals( array $values ): array {
		return array_merge(
			array(
				'orders_count'        => 0,
				'num_items_sold'      => 0,
				'total_sales'         => 0,
				'gross_sales'         => 0,
				'coupons'             => 0,
				'coupons_count'       => 0,
				'refunds'             => 0,
				'taxes'               => 0,
				'shipping'            => 0,
				'net_revenue'         => 0,
				'avg_items_per_order' => 0,
				'avg_order_value'     => 0,
				'total_customers'     => 0,
				'segments'            => array(),
			),
			$values
		);
	}

	/**
	 * Wrap a totals block into the full get_data() result shape for a single interval.
	 *
	 * The interval's subtotals are the totals without the totals-only 'products' key.
	 *
	 * @param array  $totals     The expected totals, including 'products' where applicable.
	 * @param string $date_start Interval start, in 'Y-m-d H:i:s' format.
	 * @param string $date_end   Interval end, in 'Y-m-d H:i:s' format.
	 * @return array
	 */
	protected function expected_stats_single_interval( array $totals, string $date_start, string $date_end ): array {
		$subtotals = $totals;
		unset( $subtotals['products'] );

		return array(
			'totals'    => $totals,
			'intervals' => array(
				array(
					'interval'       => substr( $date_start, 0, strlen( 'YYYY-MM-DD HH' ) ),
					'date_start'     => $date_start,
					'date_start_gmt' => $date_start,
					'date_end'       => $date_end,
					'date_end_gmt'   => $date_end,
					'subtotals'      => $subtotals,
				),
			),
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
		);
	}

	/**
	 * Assert that get_data() returns the expected result for the query args.
	 *
	 * @param array $expected_stats Expected get_data() result.
	 * @param array $query_args     Query arguments passed to get_data().
	 */
	protected function assert_report_data( array $expected_stats, array $query_args ): void {
		global $wpdb;

		$data_store = new OrdersStatsDataStore();
		$actual     = json_decode( wp_json_encode( $data_store->get_data( $query_args ) ), true );

		$this->assertEquals(
			$expected_stats,
			$actual,
			'Query args: ' . print_r( $query_args, true ) . "; query: {$wpdb->last_query}" // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		);
	}
}
