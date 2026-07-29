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
 * Tests that the proxy route only exists where proxy tracking is enabled, and
 * that dispatching a request through it goes through the untrusted-client
 * entry point rather than the trusted one.
 */
class WC_Analytics_Tracking_Proxy_Test extends BaseTestCase {

	/**
	 * Route path as registered with the REST server.
	 *
	 * @var string
	 */
	const ROUTE = '/woocommerce-analytics/v1/track';

	/**
	 * Snapshot of $_SERVER taken in set_up(), restored in tear_down(). The
	 * dispatch test below controls REQUEST_METHOD/REQUEST_URI to keep the
	 * request-shape guard in WC_Analytics_Tracking::record_event() inactive,
	 * so a passing test can only be explained by the REST controller's own
	 * call to record_client_event().
	 *
	 * @var array
	 */
	private $server_snapshot = array();

	/**
	 * Start each test with a clean REST server and no feature filters.
	 */
	public function set_up(): void {
		parent::set_up();
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		$GLOBALS['wp_rest_server'] = null;
		$this->server_snapshot     = $_SERVER;
		$this->reset_pixel_batch_queue();
	}

	/**
	 * Leave no REST server, filters, $_SERVER/$_COOKIE mutation, or queued
	 * pixel behind. Runs unconditionally regardless of the test outcome, so a
	 * failed assertion mid-test cannot leak the tk_ai cookie set by the
	 * dispatch test into the next test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'woocommerce_analytics_experimental_proxy_tracking_enabled' );
		$GLOBALS['wp_rest_server'] = null;
		$_SERVER                   = $this->server_snapshot;
		unset( $_COOKIE['tk_ai'] );
		$this->reset_pixel_batch_queue();
		parent::tear_down();
	}

	/**
	 * Read the queued pixel URLs via reflection, as
	 * WC_Analytics_Tracking_Reserved_Props_Test does.
	 *
	 * @return array
	 */
	private function get_pixel_batch_queue(): array {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}

	/**
	 * Clear the queued pixel URLs and every per-request memo, so one test's
	 * cookie, IP or event cannot leak into the next. `cached_ip` in particular
	 * survives the whole PHP process, so a test that never set REMOTE_ADDR would
	 * otherwise pin '' for every test after it.
	 */
	private function reset_pixel_batch_queue(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );

		$property = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		$ip = $reflection->getProperty( 'cached_ip' );
		$ip->setAccessible( true );
		$ip->setValue( null, null );

		$reserved = $reflection->getProperty( 'reserved_property_names' );
		$reserved->setAccessible( true );
		$reserved->setValue( null, null );

		$visitor = $reflection->getProperty( 'cached_visitor_id' );
		$visitor->setAccessible( true );
		$visitor->setValue( null, null );
	}

	/**
	 * Parse the query string of the single queued pixel into an array.
	 *
	 * @return array
	 */
	private function get_queued_pixel_props(): array {
		$queue = $this->get_pixel_batch_queue();
		$this->assertCount( 1, $queue, 'Expected exactly one queued pixel.' );

		$query = wp_parse_url( $queue[0], PHP_URL_QUERY );
		$props = array();
		parse_str( (string) $query, $props );

		return $props;
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

	/**
	 * The security boundary is WC_Analytics_Tracking::record_client_event(),
	 * not the request-shape guard in record_event() — that guard is documented
	 * as a removable net for stale MU-plugin copies. This test proves the
	 * controller itself goes through record_client_event() by dispatching a
	 * real WP_REST_Request through the REST server with $_SERVER deliberately
	 * shaped so the guard does NOT match (REQUEST_URI is the genuine
	 * `?rest_route=` form, whose parsed path is `/` — see
	 * non_proxy_request_provider() in WC_Analytics_Tracking_Reserved_Props_Test).
	 * On a plain-permalink site this is exactly what `rest_url()` produces, so
	 * it is also the realistic shape, not a contrived one.
	 *
	 * If WC_Analytics_Tracking_Proxy::track_events() is ever changed to call
	 * record_event() instead of record_client_event(), this is the only test
	 * that fails: every other test in the suite drives record_client_event()
	 * or record_event() directly rather than through a real REST dispatch, so
	 * none of them would catch a silent revert on a plain-permalink site.
	 */
	public function test_track_events_strips_reserved_properties_through_the_rest_route(): void {
		$_COOKIE['tk_ai']          = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/?rest_route=/woocommerce-analytics/v1/track';

		// Seeded so the assertions below prove the server's value replaced the
		// forged one. Unseeded, store_id is absent from the pixel entirely and
		// _via_ip is '', so an assertNotSame() would pass on absence alone.
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		update_option( 'woocommerce_store_id', 'real-store-id' );

		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::register_rest_routes();

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'event_name' => 'add_to_cart',
					'properties' => array(
						'store_id' => 'someone-elses-store',
						'_via_ip'  => '8.8.8.8',
						'pi'       => 42,
					),
				)
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );

		$props = $this->get_queued_pixel_props();

		$this->assertSame( 'real-store-id', $props['store_id'] ?? null, 'store_id must be the server value, not merely absent.' );
		$this->assertSame( '203.0.113.7', $props['_via_ip'] ?? null, '_via_ip must be the server value, not merely absent.' );
		$this->assertSame( '42', $props['pi'] ?? null, 'Event-specific properties must still survive.' );
	}

	/**
	 * The route being absent from get_routes() is a white-box fact; what actually
	 * has to hold is that a POST arriving anyway records nothing. Pinning the
	 * behaviour means a future move to "register but reject in the callback" has
	 * to be a deliberate decision rather than a silent one.
	 */
	public function test_post_records_nothing_when_proxy_tracking_is_disabled(): void {
		$_COOKIE['tk_ai']          = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/?rest_route=/woocommerce-analytics/v1/track';

		// No filter: proxy tracking is off by default.
		Woocommerce_Analytics::register_rest_routes();

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'event_name' => 'add_to_cart',
					'properties' => array( 'pi' => 42 ),
				)
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 404, $response->get_status(), 'A POST to /track must not be served where proxy tracking is off.' );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel may be queued when the route is gated off.' );
	}

	/**
	 * The batch loop is the controller's main path, and the memoized reserved
	 * list is justified by batches specifically — so a later event in the same
	 * request must still be stripped. Also covers the 207 partial-failure branch,
	 * which nothing else exercises.
	 */
	public function test_batch_strips_every_event_and_reports_per_event_results(): void {
		$_COOKIE['tk_ai']          = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/?rest_route=/woocommerce-analytics/v1/track';
		update_option( 'woocommerce_store_id', 'real-store-id' );

		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::register_rest_routes();

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					// No event_name: must fail on its own without aborting the batch.
					array( 'properties' => array( 'pi' => 1 ) ),
					array(
						'event_name' => 'add_to_cart',
						'properties' => array(
							'store_id' => 'someone-elses-store',
							'pi'       => 2,
						),
					),
				)
			)
		);

		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertSame( 207, $response->get_status() );
		$this->assertFalse( $data['results'][0]['success'] );
		$this->assertTrue( $data['results'][1]['success'] );

		$props = $this->get_queued_pixel_props();
		$this->assertSame( 'real-store-id', $props['store_id'] ?? null, 'The memoized reserved list must still strip on a later event in the batch.' );
		$this->assertSame( '2', $props['pi'] ?? null );
	}

	/**
	 * Every event becomes an outbound pixel request, so an unauthenticated caller
	 * must not be able to fan out an unbounded batch.
	 */
	public function test_batch_size_is_capped(): void {
		$_COOKIE['tk_ai']          = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/?rest_route=/woocommerce-analytics/v1/track';

		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
		Woocommerce_Analytics::register_rest_routes();

		$events = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST + 10; $i++ ) {
			$events[] = array(
				'event_name' => 'add_to_cart',
				'properties' => array( 'pi' => $i ),
			);
		}

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $events ) );

		rest_do_request( $request );

		$this->assertCount(
			WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST,
			$this->get_pixel_batch_queue(),
			'The batch must be truncated to MAX_CLIENT_EVENTS_PER_REQUEST.'
		);
	}
}
