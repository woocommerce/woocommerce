<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use WC_Helper_Order;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the double-counted refunds fix tool in the Analytics class.
 *
 * Orders are real orders, so the detection query runs against the active order
 * storage; the suite runs with HPOS both enabled and disabled.
 */
class AnalyticsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Analytics
	 */
	private $sut;

	/**
	 * Tracks events recorded by the fix tool, as [ name, properties ] pairs.
	 *
	 * @var array
	 */
	private $events = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = Analytics::get_instance();
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'no' );
		update_option( \WC_Install::INITIAL_INSTALLED_VERSION, '10.5.0' );

		$this->events = array();
		Analytics::set_refund_double_count_event_recorder(
			function ( string $event_name, array $properties ): void {
				$this->events[] = array( $event_name, $properties );
			}
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			Analytics::set_refund_double_count_event_recorder( null );
			// Action Scheduler actions are not covered by the options rollback.
			as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );
			as_unschedule_all_actions( 'woocommerce_analytics_refund_fix_batch' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Create a $50 order, refund it with the given amounts, and import it into the order stats.
	 *
	 * @param float[] $refund_amounts Refund amounts, in the order they are created.
	 * @return WC_Order
	 */
	private function create_refunded_order( array $refund_amounts ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		foreach ( $refund_amounts as $amount ) {
			wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => $amount,
				)
			);
		}

		OrdersScheduler::import( $order->get_id() );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Rewrite the order's latest refund stats row to record the whole order total,
	 * ignoring earlier refunds, as the bug fixed in #66320 did.
	 *
	 * @param WC_Order $order Order with at least two refunds.
	 */
	private function double_count_latest_refund( WC_Order $order ): void {
		global $wpdb;

		$stats_table      = $wpdb->prefix . 'wc_order_stats';
		$latest_refund_id = max( array_map( fn( $refund ) => $refund->get_id(), $order->get_refunds() ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$parent = $wpdb->get_row( $wpdb->prepare( "SELECT net_total, tax_total, shipping_total FROM {$stats_table} WHERE order_id = %d", $order->get_id() ) );
		$wpdb->update(
			$stats_table,
			array(
				'net_total'      => -1 * $parent->net_total,
				'tax_total'      => -1 * $parent->tax_total,
				'shipping_total' => -1 * $parent->shipping_total,
			),
			array( 'order_id' => $latest_refund_id )
		);
		// phpcs:enable
	}

	/**
	 * Sum of the refund stats rows of an order.
	 *
	 * @param WC_Order $order Order.
	 * @return float
	 */
	private function get_refunds_total( WC_Order $order ): float {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( net_total + tax_total + shipping_total ) FROM {$wpdb->prefix}wc_order_stats WHERE parent_id = %d",
				$order->get_id()
			)
		);
	}

	/**
	 * Highest order ID in the order stats table.
	 *
	 * @return int
	 */
	private function get_max_order_stats_id(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT MAX(order_id) FROM {$wpdb->prefix}wc_order_stats" );
	}

	/**
	 * Start a fix run through the tool and run its batches until none is left.
	 *
	 * @return int Number of batches run.
	 */
	private function run_fix(): int {
		$this->sut->run_refund_double_count_tool();

		$batches = 0;
		while ( $batches < 50 ) {
			$actions = as_get_scheduled_actions(
				array(
					'hook'     => Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK,
					'status'   => \ActionScheduler_Store::STATUS_PENDING,
					'per_page' => 1,
				)
			);
			if ( empty( $actions ) ) {
				break;
			}

			$args = reset( $actions )->get_args();
			as_unschedule_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK, $args, 'wc-admin-data' );
			$this->sut->process_refund_double_count_fix_batch( ...$args );
			++$batches;
		}

		return $batches;
	}

	/**
	 * Set the fix batch and range sizes.
	 *
	 * @param int $batch_size Parent orders re-imported per batch.
	 * @param int $range_size Order IDs checked per batch.
	 */
	private function set_batch_sizes( int $batch_size, int $range_size ): void {
		add_filter(
			'woocommerce_analytics_refund_double_count_batch_size',
			function () use ( $batch_size ) {
				return $batch_size;
			}
		);
		add_filter(
			'woocommerce_analytics_refund_double_count_range_size',
			function () use ( $range_size ) {
				return $range_size;
			}
		);
	}

	/**
	 * Properties of the recorded fix tool events with the given name, oldest first.
	 *
	 * @param string $event_name Event name without the wcadmin_ prefix.
	 * @return array[]
	 */
	private function get_events( string $event_name ): array {
		return array_values(
			array_map(
				fn( $event ) => $event[1],
				array_filter( $this->events, fn( $event ) => $event_name === $event[0] )
			)
		);
	}

	/**
	 * Get the tool registration, or null when the tool is not registered.
	 *
	 * @return array|null
	 */
	private function get_tool(): ?array {
		$tools = $this->sut->register_refund_double_count_tool( array() );

		return $tools[ Analytics::REFUND_DOUBLE_COUNT_TOOL_ID ] ?? null;
	}

	/**
	 * @testdox Fixes only orders whose refunds add up to more than the order, leaving other refund patterns alone.
	 */
	public function test_fix_repairs_only_double_counted_orders(): void {
		$double_counted = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $double_counted );
		$one_cent_double_counted = $this->create_refunded_order( array( 0.01, 49.99 ) );
		$this->double_count_latest_refund( $one_cent_double_counted );
		$correct_partial_then_full = $this->create_refunded_order( array( 20, 30 ) );
		$single_full               = $this->create_refunded_order( array( 50 ) );
		$partials_under_total      = $this->create_refunded_order( array( 10, 10 ) );

		$this->assertEqualsWithDelta( -70.0, $this->get_refunds_total( $double_counted ), 0.001, 'The fixture should over-refund the order' );

		$this->run_fix();

		$state = Analytics::get_refund_double_count_state();
		$this->assertSame( 'complete', $state['status'] );
		$this->assertSame( 2, $state['fixed'], 'Only the two double-counted orders should be fixed' );
		$this->assertSame( 0, $state['unresolved'] );
		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $double_counted ), 0.001, 'The re-import should correct the refund rows' );
		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $one_cent_double_counted ), 0.001, 'A double-counted one-cent refund should be fixed too' );
		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $correct_partial_then_full ), 0.001 );
		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $single_full ), 0.001 );
		$this->assertEqualsWithDelta( -20.0, $this->get_refunds_total( $partials_under_total ), 0.001 );

		$finished = $this->get_events( 'analytics_refund_double_count_fix_finished' );
		$this->assertCount( 1, $finished, 'The end of the run should be recorded once' );
		$this->assertSame( 'complete', $finished[0]['result'] );
		$this->assertSame( 2, $finished[0]['fixed_count'] );
		$this->assertSame( 0, $finished[0]['unresolved_count'] );
		$this->assertSame( 1, $finished[0]['batches'] );
		$this->assertSame( $state['max_order_id'], $finished[0]['max_order_id'] );
		$this->assertGreaterThanOrEqual( 0, $finished[0]['duration_seconds'] );
		$this->assertMatchesRegularExpression( '/^(mysql|mariadb)(-\d+\.\d+)?$|^unknown$/', $finished[0]['db_engine'] );
		$this->assertContains( $finished[0]['order_storage'], array( 'hpos', 'cpt' ) );
	}

	/**
	 * @testdox Does not flag refunds that match the order total apart from floating-point noise, even with many price decimals.
	 */
	public function test_fix_ignores_floating_point_noise_with_many_price_decimals(): void {
		global $wpdb;

		add_filter(
			'wc_get_price_decimals',
			function () {
				return 8;
			}
		);
		$order = $this->create_refunded_order( array( 0.1, 49.7, 0.2 ) );

		// Summed in refund ID order, -0.1 + -49.7 + -0.2 is -50.00000000000001 in floating point.
		$amounts = array( -0.1, -49.7, -0.2 );
		$ids     = array_map( fn( $refund ) => $refund->get_id(), $order->get_refunds() );
		sort( $ids );
		foreach ( $ids as $index => $refund_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$wpdb->prefix . 'wc_order_stats',
				array(
					'net_total'      => $amounts[ $index ],
					'tax_total'      => 0,
					'shipping_total' => 0,
				),
				array( 'order_id' => $refund_id )
			);
		}

		$this->run_fix();

		$state = Analytics::get_refund_double_count_state();
		$this->assertSame( 0, $state['fixed'], 'A correctly refunded order should not be re-imported' );
		$this->assertSame( 0, $state['unresolved'] );
	}

	/**
	 * @testdox Detects a double-counted refund of the smallest currency unit on a store with six price decimals.
	 */
	public function test_fix_detects_a_smallest_unit_double_count_with_six_price_decimals(): void {
		global $wpdb;

		add_filter(
			'wc_get_price_decimals',
			function () {
				return 6;
			}
		);
		$order = $this->create_refunded_order( array( 0.01, 49.99 ) );

		// A partial refund of 0.000001 followed by a full refund that recorded the whole $50 order total.
		$amounts = array( -0.000001, -50 );
		$ids     = array_map( fn( $refund ) => $refund->get_id(), $order->get_refunds() );
		sort( $ids );
		foreach ( $ids as $index => $refund_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$wpdb->prefix . 'wc_order_stats',
				array(
					'net_total'      => $amounts[ $index ],
					'tax_total'      => 0,
					'shipping_total' => 0,
				),
				array( 'order_id' => $refund_id )
			);
		}

		$this->run_fix();

		$this->assertSame( 1, Analytics::get_refund_double_count_state()['fixed'] );
		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $order ), 0.000001 );
	}

	/**
	 * @testdox Pages through full batches and order ID ranges until the highest order ID is covered.
	 */
	public function test_fix_pages_through_batches_and_ranges(): void {
		$first  = $this->create_refunded_order( array( 20, 30 ) );
		$second = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $first );
		$this->double_count_latest_refund( $second );
		$this->set_batch_sizes( 1, 1000000 );

		$batches = $this->run_fix();

		$this->assertSame( 3, $batches, 'Two full batches of one order, then a final batch that finds nothing' );
		$this->assertSame( 2, Analytics::get_refund_double_count_state()['fixed'] );
		$this->assertSame( 3, $this->get_events( 'analytics_refund_double_count_fix_finished' )[0]['batches'] );

		delete_option( Analytics::REFUND_DOUBLE_COUNT_OPTION );
		$this->double_count_latest_refund( $first );
		$this->set_batch_sizes( 100, (int) ceil( $this->get_max_order_stats_id() / 4 ) );

		$batches = $this->run_fix();

		$this->assertSame( 4, $batches, 'Each quarter of the order ID space should take one batch' );
		$this->assertSame( 'complete', Analytics::get_refund_double_count_state()['status'] );
		$this->assertSame( 1, Analytics::get_refund_double_count_state()['fixed'] );
	}

	/**
	 * @testdox Counts an order the re-import could not repair as unresolved instead of fixed.
	 */
	public function test_fix_counts_orders_it_could_not_repair(): void {
		$order = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $order );
		add_filter( 'woocommerce_analytics_is_test_order', '__return_true' );

		$this->run_fix();

		$state = Analytics::get_refund_double_count_state();
		$this->assertSame( 'complete', $state['status'] );
		$this->assertSame( 0, $state['fixed'] );
		$this->assertSame( 1, $state['unresolved'] );
		$this->assertSame( 'Check and fix', $this->get_tool()['button'], 'The tool should offer another run instead of Dismiss' );
		$this->assertSame( 1, $this->get_events( 'analytics_refund_double_count_fix_finished' )[0]['unresolved_count'] );
	}

	/**
	 * @testdox A batch from an older run does nothing.
	 */
	public function test_batch_of_an_older_run_does_nothing(): void {
		$order = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $order );
		$this->sut->run_refund_double_count_tool();
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );

		$this->sut->process_refund_double_count_fix_batch( 0, 'an-older-run' );

		$this->assertEqualsWithDelta( -70.0, $this->get_refunds_total( $order ), 0.001, 'The stale batch should not re-import anything' );
		$this->assertSame( 0, Analytics::get_refund_double_count_state()['fixed'] );
		$this->assertFalse( as_has_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK ) );
	}

	/**
	 * @testdox A batch does not overwrite a cancellation that another request saved while it was re-importing orders.
	 */
	public function test_batch_respects_a_cancellation_saved_by_another_request(): void {
		global $wpdb;

		$order = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $order );
		$this->sut->run_refund_double_count_tool();
		$run_id = Analytics::get_refund_double_count_state()['run_id'];
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );

		// Write straight to the table, as another request would, leaving this request's option cache stale.
		$cancelled = array_merge( Analytics::get_refund_double_count_state(), array( 'status' => 'cancelled' ) );
		add_action(
			'woocommerce_analytics_update_order_stats',
			function () use ( $wpdb, $cancelled ) {
				$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $cancelled ) ), array( 'option_name' => Analytics::REFUND_DOUBLE_COUNT_OPTION ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		);

		$this->sut->process_refund_double_count_fix_batch( 0, $run_id );

		wp_cache_delete( Analytics::REFUND_DOUBLE_COUNT_OPTION, 'options' );
		$this->assertSame( 'cancelled', Analytics::get_refund_double_count_state()['status'] );
		$this->assertFalse( as_has_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK ), 'The cancelled run should not schedule another batch' );
	}

	/**
	 * @testdox A batch of an older run does not write into a newer run started while it was re-importing orders.
	 */
	public function test_batch_does_not_write_into_a_newer_run(): void {
		global $wpdb;

		$order = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $order );
		$this->sut->run_refund_double_count_tool();
		$old_run_id = Analytics::get_refund_double_count_state()['run_id'];
		as_unschedule_all_actions( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK );

		$newer_run = array_merge(
			Analytics::get_refund_double_count_state(),
			array(
				'run_id' => 'newer-run',
				'fixed'  => 0,
			)
		);
		add_action(
			'woocommerce_analytics_update_order_stats',
			function () use ( $wpdb, $newer_run ) {
				$wpdb->update( $wpdb->options, array( 'option_value' => maybe_serialize( $newer_run ) ), array( 'option_name' => Analytics::REFUND_DOUBLE_COUNT_OPTION ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		);

		$this->sut->process_refund_double_count_fix_batch( 0, $old_run_id );

		wp_cache_delete( Analytics::REFUND_DOUBLE_COUNT_OPTION, 'options' );
		$state = Analytics::get_refund_double_count_state();
		$this->assertSame( 'newer-run', $state['run_id'] );
		$this->assertSame( 'running', $state['status'], 'The older batch must not complete the newer run' );
		$this->assertSame( 0, $state['fixed'], 'The older batch must not add to the newer run counters' );
	}

	/**
	 * @testdox A batch sees a run that another request created after this request cached the option as missing.
	 */
	public function test_batch_sees_a_run_created_after_the_option_was_cached_as_missing(): void {
		global $wpdb;

		$order = $this->create_refunded_order( array( 20, 30 ) );
		$this->double_count_latest_refund( $order );
		delete_option( Analytics::REFUND_DOUBLE_COUNT_OPTION );
		get_option( Analytics::REFUND_DOUBLE_COUNT_OPTION );

		// Create the run straight in the table, as another request would.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$wpdb->options,
			array(
				'option_name'  => Analytics::REFUND_DOUBLE_COUNT_OPTION,
				'option_value' => maybe_serialize(
					array(
						'run_id'       => 'run',
						'status'       => 'running',
						'max_order_id' => $this->get_max_order_stats_id(),
					)
				),
				'autoload'     => 'no',
			)
		);

		$this->sut->process_refund_double_count_fix_batch( 0, 'run' );

		$this->assertEqualsWithDelta( -50.0, $this->get_refunds_total( $order ), 0.001, 'The batch should fix the order instead of treating the run as gone' );
	}

	/**
	 * @testdox A full historical import cancels a running fix; windowed or skip-existing imports do not.
	 * @testWith [false, false, "cancelled"]
	 *           [30, false, "running"]
	 *           [false, true, "running"]
	 *
	 * @param int|bool $days          Days to import, or false for the full history.
	 * @param bool     $skip_existing Whether the import skips existing orders.
	 * @param string   $expected      Expected run status.
	 */
	public function test_regenerate_cancels_running_fix_only_for_full_reimport( $days, bool $skip_existing, string $expected ): void {
		$this->sut->run_refund_double_count_tool();

		$this->sut->maybe_cancel_refund_double_count_fix_on_regenerate( $days, $skip_existing );

		$this->assertSame( $expected, Analytics::get_refund_double_count_state()['status'] );
		$this->assertSame( 'running' === $expected, as_has_scheduled_action( Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK ) );

		$finished = $this->get_events( 'analytics_refund_double_count_fix_finished' );
		if ( 'cancelled' === $expected ) {
			$this->assertCount( 1, $finished );
			$this->assertSame( 'cancelled', $finished[0]['result'] );
			$this->assertSame( 0, $finished[0]['batches'] );
		} else {
			$this->assertCount( 0, $finished, 'A run that keeps going has not finished' );
		}
	}

	/**
	 * @testdox Registers the tool only for stores installed before 11.1.0.
	 * @testWith [null, true]
	 *           ["", true]
	 *           ["10.2.0", true]
	 *           ["11.1.0-dev", true]
	 *           ["11.1.0", false]
	 *           ["11.2.0", false]
	 *
	 * @param string|null $initial_version Initial installed version, or null when not recorded.
	 * @param bool        $expected        Whether the tool is registered.
	 */
	public function test_tool_visibility_depends_on_initial_installed_version( ?string $initial_version, bool $expected ): void {
		if ( null === $initial_version ) {
			delete_option( \WC_Install::INITIAL_INSTALLED_VERSION );
		} else {
			update_option( \WC_Install::INITIAL_INSTALLED_VERSION, $initial_version );
		}

		$this->assertSame( $expected, null !== $this->get_tool() );
	}

	/**
	 * @testdox Does not register the tool for stores that still use the old full refund data.
	 */
	public function test_tool_is_hidden_for_old_refund_data_stores(): void {
		update_option( 'woocommerce_analytics_uses_old_full_refund_data', 'yes' );

		$this->assertNull( $this->get_tool() );
	}

	/**
	 * @testdox Shows the button and status that match the run state.
	 * @dataProvider provide_tool_states
	 *
	 * @param array  $state           Stored tool state.
	 * @param bool   $pending_action  Whether a fix batch is pending.
	 * @param string $expected_button Expected button label.
	 * @param bool   $expected_off    Expected disabled flag.
	 * @param string $expected_status Expected status text fragment.
	 */
	public function test_tool_reflects_run_state( array $state, bool $pending_action, string $expected_button, bool $expected_off, string $expected_status ): void {
		update_option( Analytics::REFUND_DOUBLE_COUNT_OPTION, $state );
		if ( $pending_action ) {
			as_schedule_single_action( time() + 60, Analytics::REFUND_DOUBLE_COUNT_FIX_HOOK, array( 0, 'run' ), 'wc-admin-data' );
		}

		$tool = $this->get_tool();

		$this->assertSame( $expected_button, $tool['button'] );
		$this->assertSame( $expected_off, $tool['disabled'] );
		$this->assertStringContainsString( $expected_status, $tool['status_text'] );
	}

	/**
	 * Tool states for test_tool_reflects_run_state.
	 *
	 * @return array
	 */
	public function provide_tool_states(): array {
		return array(
			'never run'       => array( array(), false, 'Check and fix', false, '' ),
			'running'         => array(
				array(
					'run_id' => 'run',
					'status' => 'running',
					'fixed'  => 3,
				),
				true,
				'Checking and fixing…',
				true,
				'3 orders fixed so far.',
			),
			'died mid-run'    => array(
				array(
					'run_id' => 'run',
					'status' => 'running',
				),
				false,
				'Check and fix',
				false,
				'The previous run did not finish.',
			),
			'nothing found'   => array( array( 'status' => 'complete' ), false, 'Dismiss', false, 'No affected orders were found.' ),
			'fixed some'      => array(
				array(
					'status'       => 'complete',
					'fixed'        => 2,
					'completed_at' => time(),
				),
				false,
				'Dismiss',
				false,
				'Fixed 2 orders on',
			),
			'left unresolved' => array(
				array(
					'status'     => 'complete',
					'unresolved' => 1,
				),
				false,
				'Check and fix',
				false,
				'1 order could not be fixed.',
			),
		);
	}

	/**
	 * @testdox Refuses to start a run while another run or the full refund data fix is in progress.
	 * @testWith ["woocommerce_analytics_refund_double_count_fix_batch", "A fix is already in progress", "refused_running"]
	 *           ["woocommerce_analytics_refund_fix_batch", "full refund data fix is still running", "refused_full_refund_fix"]
	 *
	 * @param string $pending_hook     Hook of the pending action.
	 * @param string $expected_message Expected message fragment.
	 * @param string $expected_outcome Expected tool_run outcome.
	 */
	public function test_tool_refuses_to_start_while_busy( string $pending_hook, string $expected_message, string $expected_outcome ): void {
		update_option(
			Analytics::REFUND_DOUBLE_COUNT_OPTION,
			array(
				'run_id' => 'current',
				'status' => 'running',
			)
		);
		as_schedule_single_action( time() + 60, $pending_hook, array(), 'wc-admin-data' );

		$message = $this->sut->run_refund_double_count_tool();

		$this->assertStringContainsString( $expected_message, $message );
		$this->assertSame( 'current', Analytics::get_refund_double_count_state()['run_id'], 'No new run should start' );
		$this->assertSame(
			array(
				array(
					'outcome'         => $expected_outcome,
					'previous_status' => 'running',
				),
			),
			array_map(
				fn( $properties ) => array_intersect_key( $properties, array_flip( array( 'outcome', 'previous_status' ) ) ),
				$this->get_events( 'analytics_refund_double_count_tool_run' )
			)
		);
	}

	/**
	 * @testdox Dismisses the tool after a run that left nothing to fix.
	 */
	public function test_tool_can_be_dismissed_after_a_clean_run(): void {
		$this->run_fix();

		$this->sut->run_refund_double_count_tool();

		$this->assertTrue( Analytics::get_refund_double_count_state()['dismissed'] );
		$this->assertNull( $this->get_tool() );

		$tool_runs = $this->get_events( 'analytics_refund_double_count_tool_run' );
		$this->assertSame( array( 'started', 'dismissed' ), array_column( $tool_runs, 'outcome' ) );
		$this->assertSame( array( 'none', 'complete' ), array_column( $tool_runs, 'previous_status' ) );
	}
}
