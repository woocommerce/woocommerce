<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Analytics;
use WC_Unit_Test_Case;

/**
 * Tests for the refund double-count detection scan and fix in the Analytics class.
 */
class AnalyticsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Analytics
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = Analytics::get_instance();
		delete_option( Analytics::REFUND_DOUBLE_COUNT_OPTION );
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_order_stats" ); // phpcs:ignore WordPress.DB
		delete_option( Analytics::REFUND_DOUBLE_COUNT_OPTION );
		delete_option( 'woocommerce_analytics_uses_old_full_refund_data' );
		remove_all_filters( 'woocommerce_analytics_refund_double_count_batch_size' );
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK );
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );
		parent::tearDown();
	}

	/**
	 * Insert a row into the wc_order_stats table.
	 *
	 * @param array $overrides Column overrides.
	 */
	private function insert_stat( array $overrides ): void {
		global $wpdb;
		$row = array_merge(
			array(
				'order_id'         => 0,
				'parent_id'        => 0,
				'date_created'     => '2024-01-01 00:00:00',
				'date_created_gmt' => '2024-01-01 00:00:00',
				'num_items_sold'   => 0,
				'total_sales'      => 0,
				'tax_total'        => 0,
				'shipping_total'   => 0,
				'net_total'        => 0,
				'status'           => 'wc-completed',
				'customer_id'      => 0,
			),
			$overrides
		);
		$wpdb->insert( $wpdb->prefix . 'wc_order_stats', $row ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Persist a terminal (complete) scan state with the given count.
	 *
	 * @param int $count Affected-order count.
	 */
	private function set_complete_scan_state( int $count ): void {
		update_option(
			Analytics::REFUND_DOUBLE_COUNT_OPTION,
			array(
				'running_count' => $count,
				'complete'      => true,
			),
			false
		);
	}

	/**
	 * Force the scan/fix batch size down to one so tests can exercise the
	 * multi-batch path with a handful of rows.
	 */
	private function use_batch_size_one(): void {
		add_filter(
			'woocommerce_analytics_refund_double_count_batch_size',
			function () {
				return 1;
			}
		);
	}

	/**
	 * Create a parent order stat plus a partial refund and a buggy full refund
	 * whose rows over-sum the parent total (the #66320 signature).
	 *
	 * @param int $parent_id Parent order ID.
	 */
	private function insert_double_counted_order( int $parent_id ): void {
		$this->insert_stat(
			array(
				'order_id'       => $parent_id,
				'net_total'      => 80,
				'tax_total'      => 10,
				'shipping_total' => 10,
			)
		);
		// Partial refund of 30.
		$this->insert_stat(
			array(
				'order_id'  => $parent_id + 100000,
				'parent_id' => $parent_id,
				'net_total' => -30,
			)
		);
		// Buggy full refund: recorded -1x the whole parent total, ignoring the partial.
		$this->insert_stat(
			array(
				'order_id'       => $parent_id + 100001,
				'parent_id'      => $parent_id,
				'net_total'      => -80,
				'tax_total'      => -10,
				'shipping_total' => -10,
			)
		);
	}

	/**
	 * @testdox Scan flags a partial-then-full over-refund as one affected order.
	 */
	public function test_scan_flags_partial_then_full_over_refund(): void {
		$this->insert_double_counted_order( 1000 );

		$this->sut->process_refund_double_count_scan_batch( 0 );

		$state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $state['complete'], 'Scan should complete in a single batch' );
		$this->assertSame( 1, $state['count'], 'The over-refunded order should be counted' );
	}

	/**
	 * @testdox Scan does not flag a single full refund.
	 */
	public function test_scan_ignores_single_full_refund(): void {
		$this->insert_stat(
			array(
				'order_id'       => 2000,
				'net_total'      => 80,
				'tax_total'      => 10,
				'shipping_total' => 10,
			)
		);
		$this->insert_stat(
			array(
				'order_id'       => 2001,
				'parent_id'      => 2000,
				'net_total'      => -80,
				'tax_total'      => -10,
				'shipping_total' => -10,
			)
		);

		$this->sut->process_refund_double_count_scan_batch( 0 );

		$this->assertSame( 0, Analytics::get_refund_double_count_state()['count'], 'A single full refund is not a double-count' );
	}

	/**
	 * @testdox Scan does not flag multiple partial refunds that stay under the parent total.
	 */
	public function test_scan_ignores_partials_under_total(): void {
		$this->insert_stat(
			array(
				'order_id'       => 3000,
				'net_total'      => 80,
				'tax_total'      => 10,
				'shipping_total' => 10,
			)
		);
		$this->insert_stat(
			array(
				'order_id'  => 3001,
				'parent_id' => 3000,
				'net_total' => -20,
			)
		);
		$this->insert_stat(
			array(
				'order_id'  => 3002,
				'parent_id' => 3000,
				'net_total' => -30,
			)
		);

		$this->sut->process_refund_double_count_scan_batch( 0 );

		$this->assertSame( 0, Analytics::get_refund_double_count_state()['count'], 'Partials summing under the parent total are legitimate' );
	}

	/**
	 * @testdox Scan accumulates affected orders across multiple keyset batches.
	 */
	public function test_scan_batches_and_accumulates(): void {
		$this->insert_double_counted_order( 1000 );
		$this->insert_double_counted_order( 2000 );
		$this->use_batch_size_one();

		$this->sut->process_refund_double_count_scan_batch( 0 );
		$mid_state = Analytics::get_refund_double_count_state();
		$this->assertFalse( $mid_state['complete'], 'Scan should not complete while batches come back full' );
		$this->assertSame( 1, $mid_state['count'], 'Only the first batch order is counted so far' );
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 1000 ), 'wc-admin-data' ),
			'The next batch should be scheduled from the last processed parent_id'
		);

		$this->sut->process_refund_double_count_scan_batch( 1000 );
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['count'], 'The second batch order is accumulated' );

		$this->sut->process_refund_double_count_scan_batch( 2000 );
		$final_state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $final_state['complete'], 'Scan should complete once a batch comes back short' );
		$this->assertSame( 2, $final_state['count'], 'Both batched orders should be counted' );
	}

	/**
	 * @testdox Scan short-circuits to complete with zero count on old-data stores.
	 */
	public function test_scan_skips_old_data_stores(): void {
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );
		$this->insert_double_counted_order( 1000 );

		$this->sut->process_refund_double_count_scan_batch( 0 );

		$state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $state['complete'], 'Old-data stores mark the scan complete' );
		$this->assertSame( 0, $state['count'], 'Old-data stores are handled by their own tool, not this scan' );
	}

	/**
	 * @testdox Fix batch resets the stored count to zero once the table is swept.
	 */
	public function test_fix_batch_self_heals_count(): void {
		$this->insert_double_counted_order( 1000 );
		$this->set_complete_scan_state( 1 );

		$this->sut->process_refund_double_count_fix_batch( 0 );

		$state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $state['complete'], 'Fix keeps the scan marked complete' );
		$this->assertSame( 0, $state['count'], 'A completed fix sweep self-heals the count to zero' );
	}

	/**
	 * @testdox Fix batch advances the cursor past every selected parent and self-schedules while batches come back full.
	 */
	public function test_fix_batch_pages_with_keyset_cursor(): void {
		$this->insert_double_counted_order( 1000 );
		$this->insert_double_counted_order( 2000 );
		$this->set_complete_scan_state( 2 );
		$this->use_batch_size_one();

		$this->sut->process_refund_double_count_fix_batch( 0 );
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK, array( 1000 ), 'wc-admin-data' ),
			'The next fix batch should be scheduled from the last selected parent_id'
		);
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['count'], 'The stored count only resets on the final batch' );

		$this->sut->process_refund_double_count_fix_batch( 1000 );
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK, array( 2000 ), 'wc-admin-data' ),
			'A full batch keeps advancing the cursor'
		);

		$this->sut->process_refund_double_count_fix_batch( 2000 );
		$state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $state['complete'], 'Fix keeps the scan marked complete' );
		$this->assertSame( 0, $state['count'], 'A short batch ends the sweep and self-heals the count' );
	}

	/**
	 * @testdox Full-history regenerate with skip-existing unchecked clears the stored scan state.
	 */
	public function test_regenerate_clears_state_when_not_skipping(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_clear_refund_double_count_on_regenerate( false, false );

		$this->assertFalse( get_option( Analytics::REFUND_DOUBLE_COUNT_OPTION ), 'A full-history reprocessing re-import clears the stale count' );
	}

	/**
	 * @testdox Regenerate with skip-existing checked keeps the stored scan state.
	 */
	public function test_regenerate_keeps_state_when_skipping(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_clear_refund_double_count_on_regenerate( false, true );

		$this->assertSame( 5, Analytics::get_refund_double_count_state()['count'], 'A skip-existing import leaves affected orders untouched, so the count stays' );
	}

	/**
	 * @testdox Windowed regenerate keeps the stored scan state even when not skipping existing rows.
	 */
	public function test_regenerate_keeps_state_on_windowed_import(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_clear_refund_double_count_on_regenerate( 30, false );

		$this->assertSame( 5, Analytics::get_refund_double_count_state()['count'], 'A windowed import never reprocesses affected orders older than the window, so the count stays' );
	}
}
