<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductFailures;
use Automattic\WooCommerce\Internal\AgentReadiness\ProductScan;
use Automattic\WooCommerce\Internal\AgentReadiness\RemoteRules;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the background readiness scan.
 */
class ScheduledScanTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ScheduledScan
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Report tests stay offline: store output checks would load the test site's pages.
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		add_filter( 'woocommerce_agent_readiness_rules_url', '__return_empty_string' );
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );
		delete_option( AgentReadiness::SCAN_OPTION );
		$this->sut = wc_get_container()->get( ScheduledScan::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );
		delete_option( 'woocommerce_feature_agent_readiness_enabled' );
		parent::tearDown();
	}

	/**
	 * Ids of the pending actions for a hook.
	 *
	 * @param string $hook Action hook.
	 * @return array<int, int>
	 */
	private function pending( string $hook ): array {
		return as_get_scheduled_actions(
			array(
				'hook'   => $hook,
				'status' => 'pending',
			),
			'ids'
		);
	}

	/**
	 * @testdox Queueing twice before the scan runs leaves one pending scan.
	 */
	public function test_queue_once(): void {
		$this->assertFalse( $this->sut->is_pending() );

		$this->sut->queue();
		$this->sut->queue();

		$this->assertTrue( $this->sut->is_pending() );
		$this->assertCount( 1, $this->pending( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox Two requests that both see no pending scan, as when they run at the same time, still queue one scan.
	 */
	public function test_racing_requests_queue_once(): void {
		$racing = new class() extends ScheduledScan {
			/**
			 * Each request checked before the other's scan was saved.
			 */
			public function is_pending(): bool {
				return false;
			}
		};

		$racing->queue();
		$racing->queue();

		$this->assertCount( 1, $this->pending( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox A running daily scan counts as pending, so Run check queues nothing; the next daily scan, not yet due, doesn't.
	 */
	public function test_running_daily_scan_is_pending(): void {
		as_schedule_single_action( time() + DAY_IN_SECONDS, ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP );
		$this->assertFalse( $this->sut->is_pending(), 'A daily scan waiting for tomorrow is not pending' );

		$running = as_schedule_single_action( time(), ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP );
		\ActionScheduler::store()->log_execution( $running );

		$this->assertTrue( $this->sut->is_pending(), 'A running daily scan is pending' );
		$this->sut->queue();
		$this->assertCount( 0, $this->pending( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox A daily scan left running for over RUNNING_FOR, as when its process was killed, no longer blocks Run check.
	 */
	public function test_abandoned_daily_scan_is_not_pending(): void {
		global $wpdb;
		$running = as_schedule_single_action( time(), ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP );
		\ActionScheduler::store()->log_execution( $running );
		$wpdb->update(
			$wpdb->actionscheduler_actions,
			array( 'last_attempt_gmt' => gmdate( 'Y-m-d H:i:s', time() - ScheduledScan::RUNNING_FOR - 60 ) ),
			array( 'action_id' => $running )
		);

		$this->assertFalse( $this->sut->is_pending() );
		$this->sut->queue();
		$this->assertCount( 1, $this->pending( ScheduledScan::RUN_NOW_HOOK ), 'Run check queues a scan' );
	}

	/**
	 * @testdox A scan still waiting past STALLED_AFTER stays queued: a new request neither runs it nor queues another.
	 */
	public function test_stalled_scan_stays_queued(): void {
		delete_option( AgentReadiness::REPORT_OPTION );
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$this->assertTrue( $this->sut->is_stalled() );

		$this->sut->queue();

		$this->assertNull( wc_get_container()->get( AgentReadiness::class )->get_cached_report(), 'The scan must not run in the request.' );
		$this->assertCount( 1, $this->pending( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox A late scan on a queue that is still finishing other actions stays queued.
	 */
	public function test_late_scan_on_a_busy_queue_is_not_stalled(): void {
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$other = as_schedule_single_action( time() - 60, 'woocommerce_agent_readiness_test_other' );
		\ActionScheduler::store()->mark_complete( $other );

		$this->assertFalse( $this->sut->is_stalled() );
		as_unschedule_all_actions( 'woocommerce_agent_readiness_test_other' );
	}

	/**
	 * @testdox A late scan on a queue that recently failed or started another action is not stalled.
	 * @testWith ["failed"]
	 *           ["running"]
	 *
	 * @param string $state What happened to the other action.
	 */
	public function test_late_scan_on_a_queue_running_other_actions_is_not_stalled( string $state ): void {
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$other = as_schedule_single_action( time() - 60, 'woocommerce_agent_readiness_test_other' );
		if ( 'failed' === $state ) {
			\ActionScheduler::store()->mark_failure( $other );
		} else {
			\ActionScheduler::store()->log_execution( $other );
		}

		$this->assertFalse( $this->sut->is_stalled() );
		as_unschedule_all_actions( 'woocommerce_agent_readiness_test_other' );
	}

	/**
	 * @testdox A scan queued just now is pending, not stalled, and has a due date.
	 */
	public function test_fresh_scan_is_not_stalled(): void {
		$this->sut->queue();

		$this->assertTrue( $this->sut->is_pending() );
		$this->assertFalse( $this->sut->is_stalled() );
		$this->assertIsInt( as_next_scheduled_action( ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP ), 'The queued scan has a due date to tell a stall by.' );
	}

	/**
	 * @testdox A run stores the report and leaves exactly one daily run scheduled.
	 */
	public function test_run_stores_report_and_schedules_daily_run(): void {
		WC_Helper_Product::create_simple_product();

		$this->sut->run();
		$this->sut->run();
		$this->run_batches();

		$report = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
		$this->assertSame( 1, $report['products'] );
		$this->assertCount( 1, $this->pending( ScheduledScan::DAILY_HOOK ) );
	}

	/**
	 * @testdox A run fetches the rules file before scanning, so the new rules shape the report.
	 */
	public function test_run_fetches_rules_before_scanning(): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return 'https://rules.example/agent-readiness.json';
			}
		);
		$answer = static function () {
			return array(
				'headers'  => array(),
				'body'     => '{"version":"2026-11-01","checks":{"missing_brand":{"channels":{"google":{"field":"brand","effect":"not_listable"}}}}}',
				'response' => array(
					'code'    => 200,
					'message' => '',
				),
				'cookies'  => array(),
			);
		};
		add_filter( 'pre_http_request', $answer );
		WC_Helper_Product::create_simple_product();

		$this->sut->run();
		$this->run_batches();
		remove_filter( 'pre_http_request', $answer );
		delete_option( RemoteRules::OPTION );

		$report = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
		$this->assertSame( 1, $report['channels']['google']['not_listable'] );
	}

	/**
	 * @testdox A failed rules fetch still stores a report from the bundled checks and leaves one daily run scheduled.
	 */
	public function test_run_with_failed_fetch_uses_bundled_checks(): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return 'https://rules.example/agent-readiness.json';
			}
		);
		$fail = static function () {
			return new \WP_Error( 'http_request_failed', 'Operation timed out' );
		};
		add_filter( 'pre_http_request', $fail );
		WC_Helper_Product::create_simple_product();

		$this->sut->run();
		$this->run_batches();
		remove_filter( 'pre_http_request', $fail );

		$report = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
		$this->assertIsArray( $report );
		$this->assertSame( 1, $report['products'] );
		$this->assertContains( 'missing_images', array_column( $report['checks'], 'id' ), 'The bundled checks ran' );
		$this->assertCount( 1, $this->pending( ScheduledScan::DAILY_HOOK ) );
	}

	/**
	 * @testdox A scan that throws still leaves the next daily run scheduled.
	 */
	public function test_failed_scan_keeps_daily_run(): void {
		add_filter(
			'woocommerce_agent_readiness_rules',
			static function () {
				throw new \RuntimeException( 'Rules callback failed' );
			}
		);

		try {
			$this->sut->run();
		} catch ( \RuntimeException $e ) {
			unset( $e );
		}

		$this->assertCount( 1, $this->pending( ScheduledScan::DAILY_HOOK ) );
	}

	/**
	 * @testdox With the feature disabled a run stores nothing and schedules nothing, ending the daily chain.
	 */
	public function test_run_with_feature_disabled(): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );

		$this->sut->run();

		$this->assertNull( wc_get_container()->get( AgentReadiness::class )->get_cached_report() );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::DAILY_HOOK ) );
	}

	/**
	 * Run the scan's batch actions as Action Scheduler would, until the scan stores its report.
	 *
	 * @return int Batch actions run.
	 */
	private function run_batches(): int {
		$runs = 0;
		while ( $runs < 20 && is_array( get_option( AgentReadiness::SCAN_OPTION ) ) ) {
			$this->sut->run_batch( get_option( AgentReadiness::SCAN_OPTION )['id'] );
			++$runs;
		}
		return $runs;
	}

	/**
	 * Create published simple products quickly, without images, so they fail the image check.
	 *
	 * @param int $count Products to create.
	 * @return array<int, int> Product ids.
	 */
	private function create_products( int $count ): array {
		$ids = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'post_title'  => 'Product ' . $i,
				)
			);
			wp_set_object_terms( $id, 'simple', 'product_type' );
			update_post_meta( $id, '_regular_price', '10' );
			update_post_meta( $id, '_price', '10' );
			$ids[] = $id;
		}
		return $ids;
	}

	/**
	 * @testdox A scan tests one batch per action, then stores the same report a single pass gives.
	 */
	public function test_scan_runs_one_batch_per_action_and_matches_a_single_pass(): void {
		$this->create_products( ProductScan::BATCH_SIZE + 1 );
		$single  = wc_get_container()->get( AgentReadiness::class )->get_report();
		$batches = array();
		$count   = static function ( array $product_ids ) use ( &$batches ): void {
			$batches[] = count( $product_ids );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $count );

		$this->sut->run();
		$this->assertTrue( $this->sut->is_pending(), 'A scan testing batches is pending' );
		$runs = $this->run_batches();
		remove_action( 'woocommerce_agent_readiness_scan_batch', $count );

		$this->assertSame( array( ProductScan::BATCH_SIZE, 1 ), $batches, 'One batch per action' );
		$this->assertSame( 3, $runs, 'Two batches, then the report' );
		$this->assertFalse( $this->sut->is_pending() );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK ), 'No retry is left behind' );
		$report = wc_get_container()->get( AgentReadiness::class )->get_cached_report();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, $report['products'] );
		foreach ( array( 'products', 'available', 'channels', 'truncated' ) as $key ) {
			$this->assertSame( $single[ $key ], $report[ $key ], $key );
		}
		$this->assertSame( array_column( $single['checks'], 'count', 'id' ), array_column( $report['checks'], 'count', 'id' ) );
	}

	/**
	 * @testdox A batch that ends early is tried again from where the last finished batch stopped.
	 */
	public function test_stopped_batch_resumes(): void {
		$this->create_products( ProductScan::BATCH_SIZE + 1 );
		$this->sut->run();
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $chain );
		$crash = static function (): void {
			throw new \RuntimeException( 'Timed out' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		// Action Scheduler has started the queued batch, so it is no longer pending.
		as_unschedule_all_actions( ScheduledScan::NEXT_HOOK );

		try {
			$this->sut->run_batch( $chain );
			$this->fail( 'The batch should have stopped' );
		} catch ( \RuntimeException $e ) {
			unset( $e );
		} finally {
			remove_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		}

		$retry = as_next_scheduled_action( ScheduledScan::NEXT_HOOK, array( $chain ), ScheduledScan::GROUP );
		$this->assertIsInt( $retry, 'A retry is queued' );
		$this->assertGreaterThan( time() + ScheduledScan::RETRY_AFTER / 2, $retry, 'The retry waits for the stopped action to time out' );
		$this->assertSame( ProductScan::BATCH_SIZE, get_option( AgentReadiness::SCAN_OPTION )['position'], 'The finished batch is kept' );

		$this->run_batches();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
	}

	/**
	 * @testdox A batch that fails every time ends the scan after MAX_ATTEMPTS, keeping the last report.
	 */
	public function test_batch_that_always_fails_gives_up(): void {
		$this->create_products( 1 );
		update_option( AgentReadiness::REPORT_OPTION, array( 'products' => 7 ) );
		$this->sut->run();
		$crash = static function (): void {
			throw new \RuntimeException( 'Fatal on this product' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];

		for ( $i = 0; $i < AgentReadiness::MAX_ATTEMPTS; $i++ ) {
			try {
				$this->sut->run_batch( $chain );
			} catch ( \RuntimeException $e ) {
				unset( $e );
			}
		}
		remove_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		$this->sut->run_batch( $chain );

		$this->assertFalse( get_option( AgentReadiness::SCAN_OPTION ) );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK, array( $chain ) ), 'No retry is left behind' );
		$this->assertSame( 7, get_option( AgentReadiness::REPORT_OPTION )['products'] );
	}

	/**
	 * @testdox Giving up after a finished batch removes failure rows that batch wrote for products the last report never covered.
	 */
	public function test_give_up_drops_failure_rows_the_last_report_did_not_cover(): void {
		$ids = $this->create_products( ProductScan::BATCH_SIZE + 1 );
		update_option( AgentReadiness::REPORT_OPTION, array( 'products' => 7 ) );
		$this->sut->run();
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $chain );
		// The scan walks newest products first, so the first batch covers the last id created, not $ids[0].
		$covered = (int) end( $ids );
		$this->assertNotEmpty( ProductFailures::stored( $covered ), 'The finished batch wrote failure rows' );

		$crash = static function (): void {
			throw new \RuntimeException( 'Fatal on the next batch' );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		for ( $i = 0; $i < AgentReadiness::MAX_ATTEMPTS; $i++ ) {
			try {
				$this->sut->run_batch( $chain );
			} catch ( \RuntimeException $e ) {
				unset( $e );
			}
		}
		remove_action( 'woocommerce_agent_readiness_scan_batch', $crash );
		$this->sut->run_batch( $chain );

		$this->assertFalse( get_option( AgentReadiness::SCAN_OPTION ) );
		$this->assertSame( array(), ProductFailures::stored( $covered ), 'Rows the abandoned scan added are removed' );
		$this->assertSame( 7, get_option( AgentReadiness::REPORT_OPTION )['products'] );
	}

	/**
	 * @testdox A new scan request during a scan starts over, and the replaced scan's actions do nothing.
	 */
	public function test_new_request_restarts_the_scan(): void {
		$this->create_products( ProductScan::BATCH_SIZE + 1 );
		$this->sut->run();
		$first = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $first );

		$this->sut->queue();
		$this->assertTrue( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ), 'A request during a scan queues a new one' );
		$this->sut->run();
		$second = get_option( AgentReadiness::SCAN_OPTION );

		$this->assertNotSame( $first, $second['id'] );
		$this->assertSame( 0, $second['position'] );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK, array( $first ) ), 'The old scan has nothing left to run' );
		$this->sut->run_batch( $first );
		$this->assertSame( 0, get_option( AgentReadiness::SCAN_OPTION )['position'], 'The old scan changes nothing' );

		$this->run_batches();
		$this->assertSame( ProductScan::BATCH_SIZE + 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
	}

	/**
	 * @testdox A batch replaced mid-run does not store the failure rows it collected.
	 */
	public function test_replaced_batch_does_not_store_failures(): void {
		$ids = $this->create_products( ProductScan::BATCH_SIZE + 1 );
		$this->sut->run();
		$first   = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$covered = (int) end( $ids );

		$replace = function (): void {
			$this->sut->run();
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $replace );
		$this->sut->run_batch( $first );
		remove_action( 'woocommerce_agent_readiness_scan_batch', $replace );

		$this->assertNotSame( $first, get_option( AgentReadiness::SCAN_OPTION )['id'] );
		$this->assertSame( array(), ProductFailures::stored( $covered ), 'The replaced batch stored nothing' );
	}

	/**
	 * @testdox A report that fails to store leaves the previous report's failure rows in place.
	 */
	public function test_failed_report_write_keeps_the_previous_rows(): void {
		$stale = $this->create_products( 1 )[0];
		ProductFailures::store( $stale, array() );
		$this->hide_product( $stale );
		update_option( AgentReadiness::REPORT_OPTION, array( 'products' => 4 ) );
		$this->create_products( 1 );
		$this->sut->run();
		$keep_old = static function ( $value, $old ) {
			unset( $value );
			return $old;
		};
		add_filter( 'pre_update_option_' . AgentReadiness::REPORT_OPTION, $keep_old, 10, 2 );
		try {
			$this->run_batches();
		} finally {
			remove_filter( 'pre_update_option_' . AgentReadiness::REPORT_OPTION, $keep_old, 10 );
		}

		$this->assertSame( array( ProductFailures::CHECKED ), ProductFailures::stored( $stale ) );
		$this->assertSame( 4, get_option( AgentReadiness::REPORT_OPTION )['products'] );
	}

	/**
	 * @testdox Rows saved after a product was skipped by its batch are still there when the scan finishes.
	 */
	public function test_rows_saved_after_a_skipped_product_stay(): void {
		$kept  = $this->create_products( 1 )[0];
		$stale = $this->create_products( 1 )[0];
		ProductFailures::store( $kept, array() );
		ProductFailures::store( $stale, array() );
		$this->hide_product( $stale );
		$skip = function () use ( $kept ): void {
			$this->hide_product( $kept );
		};
		add_action( 'woocommerce_agent_readiness_scan_batch', $skip );
		$this->sut->run();
		$this->sut->run_batch( get_option( AgentReadiness::SCAN_OPTION )['id'] );
		remove_action( 'woocommerce_agent_readiness_scan_batch', $skip );
		wp_set_object_terms( $kept, array(), 'product_visibility' );
		clean_post_cache( $kept );
		ProductFailures::store( $kept, array() );

		$this->run_batches();

		$this->assertSame( array( ProductFailures::CHECKED ), ProductFailures::stored( $kept ) );
		$this->assertSame( array(), ProductFailures::stored( $stale ), 'Rows from before the scan, on a product it did not test, are removed' );
	}

	/**
	 * @testdox Giving up after the report is stored keeps the rows that report counts.
	 */
	public function test_give_up_after_the_report_is_stored_keeps_its_rows(): void {
		$product_id = $this->create_products( 1 )[0];
		$this->sut->run();
		$this->run_batches();
		$report = get_option( AgentReadiness::REPORT_OPTION );
		$this->assertNotEmpty( ProductFailures::stored( $product_id ) );

		update_option(
			AgentReadiness::SCAN_OPTION,
			array(
				'id'         => $report['scan_id'],
				'attempts'   => AgentReadiness::MAX_ATTEMPTS,
				'ids'        => array( $product_id ),
				'had_rows'   => array(),
				'tested'     => array( $product_id ),
				'position'   => 1,
				'rows_as_of' => microtime( true ),
			),
			false
		);
		$this->sut->run_batch( $report['scan_id'] );

		$this->assertFalse( get_option( AgentReadiness::SCAN_OPTION ) );
		$this->assertContains( ProductFailures::CHECKED, ProductFailures::stored( $product_id ) );
		$this->assertArrayNotHasKey( 'scan_id', wc_get_container()->get( AgentReadiness::class )->get_cached_report() );
	}

	/**
	 * @testdox An empty product list from a successful query is an empty catalog, and a query error stops the scan.
	 */
	public function test_empty_product_list_follows_the_query(): void {
		$this->create_products( 1 );
		$selection = static function ( $posts, $query ) {
			if ( ProductScan::PRODUCT_LIMIT + 1 !== (int) $query->get( 'posts_per_page' ) ) {
				return $posts;
			}
			return array();
		};
		add_filter( 'posts_pre_query', $selection, 10, 2 );
		try {
			$this->assertSame( array(), ( new ProductScan() )->start() );
		} finally {
			remove_filter( 'posts_pre_query', $selection, 10 );
		}

		$fail = static function ( $posts, $query ) {
			if ( ProductScan::PRODUCT_LIMIT + 1 !== (int) $query->get( 'posts_per_page' ) ) {
				return $posts;
			}
			global $wpdb;
			$wpdb->last_error = 'database unavailable';
			return array();
		};
		add_filter( 'posts_pre_query', $fail, 10, 2 );
		try {
			$this->expectException( \RuntimeException::class );
			( new ProductScan() )->start();
		} finally {
			remove_filter( 'posts_pre_query', $fail, 10 );
			global $wpdb;
			$wpdb->last_error = '';
		}
	}

	/**
	 * Set a product to Catalog visibility Hidden without saving it through the product editor.
	 *
	 * @param int $product_id Product id.
	 */
	private function hide_product( int $product_id ): void {
		wp_set_object_terms( $product_id, array( 'exclude-from-catalog', 'exclude-from-search' ), 'product_visibility' );
		clean_post_cache( $product_id );
	}

	/**
	 * @testdox Turning the feature off during a scan stops it at the next batch, storing nothing.
	 */
	public function test_feature_off_stops_the_scan(): void {
		$this->create_products( ProductScan::BATCH_SIZE + 1 );
		$this->sut->run();
		$chain = get_option( AgentReadiness::SCAN_OPTION )['id'];
		$this->sut->run_batch( $chain );

		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		$this->sut->run_batch( $chain );

		$this->assertFalse( get_option( AgentReadiness::SCAN_OPTION ) );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::NEXT_HOOK ) );
		$this->assertNull( wc_get_container()->get( AgentReadiness::class )->get_cached_report() );
	}

	/**
	 * Date every finished, failed or running action an hour back, so only what the
	 * test does next counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status IN ( 'complete', 'failed', 'in-progress' )", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
	}
}
