<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\ReportAbility;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the route WooCommerce.com reads a connected store's agent readiness report through.
 */
class WccomSiteReportRouteTest extends WC_REST_Unit_Test_Case {

	private const ROUTE = '/wccom-site/v3/agent-readiness';

	/**
	 * @testdox The route is registered.
	 */
	public function test_route_is_registered(): void {
		$this->assertArrayHasKey( self::ROUTE, rest_get_server()->get_routes() );
	}

	/**
	 * @testdox A request WooCommerce.com did not authenticate is refused.
	 */
	public function test_unauthenticated_request_is_refused(): void {
		wp_set_current_user( 0 );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'not_authenticated', $response->get_data()['code'] );
	}

	/**
	 * @testdox A user who can't manage the store is refused.
	 */
	public function test_user_without_the_capability_is_refused(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'forbidden', $response->get_data()['code'] );
	}

	/**
	 * @testdox A store manager gets what the agent readiness ability returns.
	 */
	public function test_returns_the_report(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertSame( ReportAbility::execute( array() ), $data['data'] );
		$this->assertArrayHasKey( 'scan_pending', $data['data'] );
	}
}
