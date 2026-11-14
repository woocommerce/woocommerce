<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
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
		$this->sut = wc_get_container()->get( ScheduledScan::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
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
	 * Date every finished, failed or running action an hour back, so only what the
	 * test does next counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status IN ( 'complete', 'failed', 'in-progress' )", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
	}
}
