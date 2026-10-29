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
		as_unschedule_all_actions( ScheduledScan::RECHECK_HOOK );
		$this->sut = wc_get_container()->get( ScheduledScan::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		as_unschedule_all_actions( ScheduledScan::DAILY_HOOK );
		as_unschedule_all_actions( ScheduledScan::RECHECK_HOOK );
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
		as_schedule_single_action( time() - ScheduledScan::STALLED_AFTER - 60, ScheduledScan::RUN_NOW_HOOK, array(), ScheduledScan::GROUP );
		$this->assertTrue( $this->sut->is_stalled() );

		$this->sut->queue();

		$this->assertFalse( $this->sut->is_pending() );
		$this->assertSame( 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
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
	 * @testdox Product changes before the first report queue no re-check.
	 */
	public function test_product_change_without_report_queues_nothing(): void {
		WC_Helper_Product::create_simple_product();

		$this->assertCount( 0, $this->pending( ScheduledScan::RECHECK_HOOK ) );
	}

	/**
	 * @testdox Saves, a variation save and a trash share one re-check, which drops the fixed product from the report.
	 */
	public function test_product_changes_share_one_recheck(): void {
		$product   = WC_Helper_Product::create_simple_product();
		$variable  = WC_Helper_Product::create_variation_product();
		$readiness = wc_get_container()->get( AgentReadiness::class );
		$readiness->refresh_report();
		$this->assertContains( $product->get_id(), $this->example_ids( 'missing_brand' ) );

		wp_set_object_terms( $product->get_id(), 'Acme', 'product_brand' );
		$product->save();
		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_regular_price( '12' );
		$variation->save();
		$variable->delete();

		$pending = $this->pending( ScheduledScan::RECHECK_HOOK );
		$this->assertCount( 1, $pending );
		$due = as_next_scheduled_action( ScheduledScan::RECHECK_HOOK, array(), ScheduledScan::GROUP );
		$this->assertGreaterThan( time() + ScheduledScan::RECHECK_DELAY - 60, $due, 'The re-check waits so a burst of saves shares it' );

		$this->sut->recheck();

		$report = $readiness->get_cached_report();
		$this->assertSame( 1, $report['products'], 'The trashed variable product is gone' );
		$this->assertNotContains( $product->get_id(), $this->example_ids( 'missing_brand' ), 'The product with a brand now passes' );
	}

	/**
	 * @testdox Creating a product after a report exists queues a re-check.
	 */
	public function test_new_product_queues_recheck(): void {
		wc_get_container()->get( AgentReadiness::class )->refresh_report();

		WC_Helper_Product::create_simple_product();

		$this->assertCount( 1, $this->pending( ScheduledScan::RECHECK_HOOK ) );
	}

	/**
	 * @testdox With the feature disabled a re-check leaves the stored report as it was.
	 */
	public function test_recheck_with_feature_disabled(): void {
		$readiness = wc_get_container()->get( AgentReadiness::class );
		$readiness->refresh_report();
		WC_Helper_Product::create_simple_product();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );

		$this->sut->recheck();

		$this->assertSame( 0, $readiness->get_cached_report()['products'] );
	}

	/**
	 * @testdox A product change while a requested scan is waiting queues no re-check.
	 */
	public function test_product_change_with_pending_scan(): void {
		wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$this->sut->queue();

		WC_Helper_Product::create_simple_product();

		$this->assertCount( 0, $this->pending( ScheduledScan::RECHECK_HOOK ) );
	}

	/**
	 * @testdox A re-check rebuilds the report without fetching the rules file or moving the daily run.
	 */
	public function test_recheck_skips_rules_fetch(): void {
		add_filter(
			'woocommerce_agent_readiness_rules_url',
			static function () {
				return 'https://rules.example/agent-readiness.json';
			}
		);
		$requests = 0;
		$count    = static function ( $preempt ) use ( &$requests ) {
			++$requests;
			return $preempt;
		};
		add_filter( 'pre_http_request', $count );
		WC_Helper_Product::create_simple_product();
		$daily = as_schedule_single_action( time() + DAY_IN_SECONDS, ScheduledScan::DAILY_HOOK, array(), ScheduledScan::GROUP );

		$this->sut->recheck();
		remove_filter( 'pre_http_request', $count );

		$this->assertSame( 0, $requests );
		$this->assertSame( 1, wc_get_container()->get( AgentReadiness::class )->get_cached_report()['products'] );
		$this->assertSame( array( $daily ), array_map( 'intval', $this->pending( ScheduledScan::DAILY_HOOK ) ) );
	}

	/**
	 * Example product ids the stored report keeps for a check.
	 *
	 * @param string $check_id Check id.
	 * @return array<int, int>
	 */
	private function example_ids( string $check_id ): array {
		$checks = wc_get_container()->get( AgentReadiness::class )->get_cached_report()['checks'];
		foreach ( $checks as $check ) {
			if ( $check_id === $check['id'] ) {
				return $check['product_ids'];
			}
		}
		return array();
	}
}
