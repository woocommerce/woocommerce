<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the agent readiness REST endpoints.
 */
class ReadinessRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Enable the feature before the routes register.
	 */
	public function setUp(): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		parent::setUp();
		delete_option( AgentReadiness::REPORT_OPTION );
		as_unschedule_all_actions( ScheduledScan::RUN_NOW_HOOK );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
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
	 * @testdox GET returns a null report before the first scan, then the stored report.
	 */
	public function test_get_returns_stored_report(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'report'       => null,
				'scan_pending' => false,
			),
			$response->get_data()
		);

		$report   = wc_get_container()->get( AgentReadiness::class )->refresh_report();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) );
		$this->assertSame( $report['generated_at'], $response->get_data()['report']['generated_at'] );
	}

	/**
	 * @testdox POST scan queues a background scan and answers 202 with scan_pending.
	 */
	public function test_post_queues_scan(): void {
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/scan' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertTrue( $response->get_data()['scan_pending'] );
		$this->assertTrue( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox A user without manage_woocommerce can't read the report or queue a scan.
	 */
	public function test_requires_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) )->get_status() );
		$this->assertSame( 403, $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/scan' ) )->get_status() );
		$this->assertFalse( as_has_scheduled_action( ScheduledScan::RUN_NOW_HOOK ) );
	}

	/**
	 * @testdox With the feature disabled the routes are not registered.
	 */
	public function test_routes_absent_when_feature_disabled(): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->assertSame( 404, $wp_rest_server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) )->get_status() );
	}
}
