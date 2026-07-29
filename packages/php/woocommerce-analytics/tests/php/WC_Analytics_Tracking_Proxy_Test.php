<?php
/**
 * Tests for the tracking proxy REST route.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Woocommerce_Analytics;
use WorDBless\BaseTestCase;

/**
 * Tests that the proxy route only exists where proxy tracking is enabled.
 */
class WC_Analytics_Tracking_Proxy_Test extends BaseTestCase {

	/**
	 * Route path as registered with the REST server.
	 *
	 * @var string
	 */
	const ROUTE = '/woocommerce-analytics/v1/track';

	/**
	 * Start each test with a clean REST server and no feature filters.
	 */
	public function set_up(): void {
		parent::set_up();
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		$GLOBALS['wp_rest_server'] = null;
	}

	/**
	 * Leave no REST server or filters behind.
	 */
	public function tear_down(): void {
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		$GLOBALS['wp_rest_server'] = null;
		parent::tear_down();
	}

	/**
	 * The endpoint is unauthenticated by design, so it must not exist on the
	 * sites that never opted into proxy tracking — which is every site, by
	 * default.
	 */
	public function test_route_is_not_registered_when_proxy_tracking_is_disabled(): void {
		Woocommerce_Analytics::register_rest_routes();

		$this->assertArrayNotHasKey( self::ROUTE, rest_get_server()->get_routes() );
	}

	/**
	 * The endpoint still exists where the feature is on, otherwise the client
	 * has nowhere to post.
	 */
	public function test_route_is_registered_when_proxy_tracking_is_enabled(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		Woocommerce_Analytics::register_rest_routes();

		$this->assertArrayHasKey( self::ROUTE, rest_get_server()->get_routes() );
	}
}
