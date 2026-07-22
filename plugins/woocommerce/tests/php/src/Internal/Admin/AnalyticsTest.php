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
	 * Persist an incomplete scan state, optionally with attempt tracking fields.
	 *
	 * @param array $extra Extra state keys (scan_attempts, last_scan_attempt).
	 */
	private function set_incomplete_scan_state( array $extra = array() ): void {
		update_option(
			Analytics::REFUND_DOUBLE_COUNT_OPTION,
			array_merge(
				array(
					'running_count' => 0,
					'complete'      => false,
				),
				$extra
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
	 * @testdox Scan accumulates affected orders across multiple full-LIMIT batches within one window.
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
			'A full batch should schedule the next one from the last processed parent_id, staying inside the window'
		);

		$this->sut->process_refund_double_count_scan_batch( 1000 );
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['count'], 'The second batch order is accumulated' );

		$this->sut->process_refund_double_count_scan_batch( 2000 );
		$final_state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $final_state['complete'], 'Scan should complete once the last window comes back short' );
		$this->assertSame( 2, $final_state['count'], 'Both batched orders should be counted' );
	}

	/**
	 * @testdox Scan advances window by window and only completes past the highest order_id.
	 */
	public function test_scan_advances_across_windows(): void {
		$this->insert_double_counted_order( 1000 );
		$this->insert_double_counted_order( 600000 );

		$this->sut->process_refund_double_count_scan_batch( 0 );
		$mid_state = Analytics::get_refund_double_count_state();
		$this->assertFalse( $mid_state['complete'], 'A short batch must not end the scan while rows exist past the window' );
		$this->assertSame( 1, $mid_state['count'], 'Only the first window order is counted so far' );
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( Analytics::REFUND_DOUBLE_COUNT_WINDOW ), 'wc-admin-data' ),
			'An exhausted window should schedule the next batch from the window boundary'
		);

		$this->sut->process_refund_double_count_scan_batch( Analytics::REFUND_DOUBLE_COUNT_WINDOW );
		$final_state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $final_state['complete'], 'Scan completes once the window covering the highest order_id is swept' );
		$this->assertSame( 2, $final_state['count'], 'Orders from both windows should be counted' );
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
		$this->assertSame( 0, $state['count'], 'A short batch in the last window ends the sweep and self-heals the count' );
	}

	/**
	 * @testdox Fix batch advances window by window and only self-heals past the highest order_id.
	 */
	public function test_fix_batch_advances_across_windows(): void {
		$this->insert_double_counted_order( 1000 );
		$this->insert_double_counted_order( 600000 );
		$this->set_complete_scan_state( 2 );

		$this->sut->process_refund_double_count_fix_batch( 0 );
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK, array( Analytics::REFUND_DOUBLE_COUNT_WINDOW ), 'wc-admin-data' ),
			'An exhausted window should schedule the next fix batch from the window boundary'
		);
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['count'], 'The stored count only resets once the sweep reaches the last window' );

		$this->sut->process_refund_double_count_fix_batch( Analytics::REFUND_DOUBLE_COUNT_WINDOW );
		$state = Analytics::get_refund_double_count_state();
		$this->assertTrue( $state['complete'], 'Fix keeps the scan marked complete' );
		$this->assertSame( 0, $state['count'], 'The sweep self-heals once the window covering the highest order_id is done' );
	}

	/**
	 * @testdox Self-heal reschedules an incomplete scan and counts the attempt.
	 */
	public function test_self_heal_reschedules_incomplete_scan(): void {
		$this->set_incomplete_scan_state();

		Analytics::maybe_reschedule_refund_double_count_scan();

		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'An incomplete scan with no pending action should be rescheduled from zero'
		);
		$state = Analytics::get_refund_double_count_state();
		$this->assertSame( 1, $state['attempts'], 'The reschedule should be recorded as an attempt' );
		$this->assertGreaterThan( 0, $state['last_attempt'], 'The attempt timestamp should be recorded' );
	}

	/**
	 * @testdox Self-heal does nothing when the state option does not exist.
	 */
	public function test_self_heal_noops_without_state_option(): void {
		Analytics::maybe_reschedule_refund_double_count_scan();

		$this->assertFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'Stores that never owed a scan (fresh installs) must not schedule one'
		);
	}

	/**
	 * @testdox Self-heal does nothing once the scan is complete.
	 */
	public function test_self_heal_noops_when_complete(): void {
		$this->set_complete_scan_state( 3 );

		Analytics::maybe_reschedule_refund_double_count_scan();

		$this->assertFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'A completed scan must not be rescheduled'
		);
	}

	/**
	 * @testdox Self-heal does nothing while a scan action is already pending.
	 */
	public function test_self_heal_noops_when_scan_pending(): void {
		$this->set_incomplete_scan_state();
		as_schedule_single_action( time() + 3600, Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 500000 ), 'wc-admin-data' );

		Analytics::maybe_reschedule_refund_double_count_scan();

		$this->assertFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'A pending scan action must not be duplicated'
		);
		$this->assertSame( 0, Analytics::get_refund_double_count_state()['attempts'], 'No attempt is consumed when a scan is already queued' );
	}

	/**
	 * @testdox Self-heal does nothing while a historical import is running.
	 */
	public function test_self_heal_noops_while_importing(): void {
		$this->set_incomplete_scan_state();
		as_schedule_single_action( time() + 3600, 'wc-admin_import_batch_init_orders', array(), 'wc-admin-data' );

		try {
			Analytics::maybe_reschedule_refund_double_count_scan();

			$this->assertFalse(
				as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
				'Scanning while an import rewrites wc_order_stats would count a moving target'
			);
		} finally {
			as_unschedule_all_actions( 'wc-admin_import_batch_init_orders' );
		}
	}

	/**
	 * @testdox Self-heal waits out the cooldown between attempts.
	 */
	public function test_self_heal_respects_cooldown(): void {
		$this->set_incomplete_scan_state(
			array(
				'scan_attempts'     => 1,
				'last_scan_attempt' => time(),
			)
		);

		Analytics::maybe_reschedule_refund_double_count_scan();
		$this->assertFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'A recent attempt must not be retried before the cooldown elapses'
		);

		$this->set_incomplete_scan_state(
			array(
				'scan_attempts'     => 1,
				'last_scan_attempt' => time() - ( 2 * HOUR_IN_SECONDS ),
			)
		);

		Analytics::maybe_reschedule_refund_double_count_scan();
		$this->assertNotFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'A stale attempt should be retried once the cooldown has elapsed'
		);
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['attempts'], 'The retry should increment the attempt counter' );
	}

	/**
	 * @testdox Self-heal stops retrying once the attempt cap is reached.
	 */
	public function test_self_heal_caps_attempts(): void {
		$this->set_incomplete_scan_state(
			array(
				'scan_attempts'     => Analytics::REFUND_DOUBLE_COUNT_MAX_SCAN_ATTEMPTS,
				'last_scan_attempt' => time() - ( 2 * HOUR_IN_SECONDS ),
			)
		);

		Analytics::maybe_reschedule_refund_double_count_scan();

		$this->assertFalse(
			as_next_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_SCAN_HOOK, array( 0 ), 'wc-admin-data' ),
			'The scan must not be retried past the attempt cap'
		);
	}

	/**
	 * @testdox Scan progress writes preserve the attempt tracking fields.
	 */
	public function test_scan_progress_preserves_attempt_tracking(): void {
		$this->insert_double_counted_order( 1000 );
		$this->insert_double_counted_order( 2000 );
		$this->set_incomplete_scan_state(
			array(
				'scan_attempts'     => 2,
				'last_scan_attempt' => time() - HOUR_IN_SECONDS,
			)
		);
		$this->use_batch_size_one();

		$this->sut->process_refund_double_count_scan_batch( 0 );

		$this->assertSame( 2, Analytics::get_refund_double_count_state()['attempts'], 'A mid-scan progress write must not reset the retry budget' );
	}

	/**
	 * @testdox Full-history regenerate with skip-existing unchecked resets the scan state for re-verification.
	 */
	public function test_regenerate_resets_state_when_not_skipping(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_reset_refund_double_count_on_regenerate( false, false );

		$state = Analytics::get_refund_double_count_state();
		$this->assertNotFalse( get_option( Analytics::REFUND_DOUBLE_COUNT_OPTION ), 'The state must survive so a verification scan can run after the import' );
		$this->assertFalse( $state['complete'], 'The stale result is discarded: the notice hides until the post-import scan completes' );
		$this->assertSame( 0, $state['count'], 'The stale count is discarded' );
		$this->assertSame( 0, $state['attempts'], 'A fresh scan cycle gets a fresh retry budget' );
	}

	/**
	 * @testdox Regenerate with skip-existing checked keeps the stored scan state.
	 */
	public function test_regenerate_keeps_state_when_skipping(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_reset_refund_double_count_on_regenerate( false, true );

		$this->assertSame( 5, Analytics::get_refund_double_count_state()['count'], 'A skip-existing import leaves affected orders untouched, so the count stays' );
	}

	/**
	 * @testdox Windowed regenerate keeps the stored scan state even when not skipping existing rows.
	 */
	public function test_regenerate_keeps_state_on_windowed_import(): void {
		$this->set_complete_scan_state( 5 );

		$this->sut->maybe_reset_refund_double_count_on_regenerate( 30, false );

		$this->assertSame( 5, Analytics::get_refund_double_count_state()['count'], 'A windowed import never reprocesses affected orders older than the window, so the count stays' );
	}
}
