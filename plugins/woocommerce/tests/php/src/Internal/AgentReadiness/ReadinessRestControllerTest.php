<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\ScheduledScan;
use Automattic\WooCommerce\Internal\AgentReadiness\UcpSwitch;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the agent readiness REST endpoints.
 */
class ReadinessRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Enable the feature before the routes register. A filter, not an option
	 * write, since the test transaction starts in parent::setUp().
	 */
	public function setUp(): void {
		add_filter( 'pre_option_woocommerce_feature_agent_readiness_enabled', array( __CLASS__, 'enabled' ) );
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
		remove_filter( 'pre_option_woocommerce_feature_agent_readiness_enabled', array( __CLASS__, 'enabled' ) );
		$this->set_permalink_structure( '' );
		parent::tearDown();
	}

	/**
	 * The feature option's value while these tests run.
	 */
	public static function enabled(): string {
		return 'yes';
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
		remove_filter( 'pre_option_woocommerce_feature_agent_readiness_enabled', array( __CLASS__, 'enabled' ) );
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'no' );
		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->assertSame( 404, $wp_rest_server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/agent-readiness' ) )->get_status() );
	}

	/**
	 * @testdox POST ucp refuses to turn UCP on while a required setup check fails.
	 */
	public function test_ucp_switch_blocked_by_failing_check(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );

		$response = $this->dispatch_ucp( true );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'woocommerce_agentic_commerce_setup_incomplete', $response->get_data()['code'] );
		$this->assertContains( 'https', $response->get_data()['data']['failing_checks'] );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox POST ucp turns UCP on once the required checks pass, and off again.
	 */
	public function test_ucp_switch_on_and_off(): void {
		SetupChecksTest::make_store_ready( $this );

		$response = $this->dispatch_ucp( true );
		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['enabled'] );
		$this->assertSame( UcpSwitch::STATUS_ACTIVE, $response->get_data()['status'] );
		$this->assertSame( 'yes', get_option( 'woocommerce_feature_ucp_enabled' ) );

		$response = $this->dispatch_ucp( false );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( UcpSwitch::STATUS_OFF, $response->get_data()['status'] );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox Turning UCP off works while a required check fails.
	 */
	public function test_ucp_switch_off_with_failing_check(): void {
		update_option( 'woocommerce_feature_ucp_enabled', 'yes' );

		$response = $this->dispatch_ucp( false );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * @testdox A user without manage_woocommerce can't switch UCP.
	 */
	public function test_ucp_switch_requires_manage_woocommerce(): void {
		SetupChecksTest::make_store_ready( $this );
		update_option( 'woocommerce_feature_ucp_enabled', 'no' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertSame( 403, $this->dispatch_ucp( true )->get_status() );
		$this->assertSame( 'no', get_option( 'woocommerce_feature_ucp_enabled' ) );
	}

	/**
	 * Send POST wc/v3/agent-readiness/ucp.
	 *
	 * @param bool $enabled Whether to turn UCP on.
	 */
	private function dispatch_ucp( bool $enabled ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v3/agent-readiness/ucp' );
		$request->set_param( 'enabled', $enabled );
		return $this->server->dispatch( $request );
	}
}
