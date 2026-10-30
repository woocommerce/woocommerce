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
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
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
	 * @testdox A scan still waiting past STALLED_AFTER runs in the next request instead of waiting.
	 */
	public function test_stalled_scan_runs_now(): void {
		WC_Helper_Product::create_simple_product();
		$this->age_finished_actions();
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$this->assertTrue( $this->sut->is_stalled() );

		$this->sut->queue();

		$this->assertFalse( $this->sut->is_pending() );
		$this->assertSame( 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
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
	 * Date every finished action an hour back, so only what the test does next
	 * counts as recent queue activity.
	 */
	private function age_finished_actions(): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}actionscheduler_actions SET last_attempt_gmt = %s WHERE status = 'complete'", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
	}
}
