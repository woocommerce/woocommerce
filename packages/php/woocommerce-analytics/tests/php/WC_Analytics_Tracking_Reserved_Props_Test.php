<?php
/**
 * Tests for the reserved-property set that keeps client-supplied event
 * properties from overriding the ones the server derives.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests for WC_Analytics_Tracking reserved property handling.
 */
class WC_Analytics_Tracking_Reserved_Props_Test extends BaseTestCase {

	/**
	 * Snapshot of $_SERVER taken in set_up(), restored in tear_down().
	 *
	 * Several tests mutate REQUEST_METHOD/REQUEST_URI to exercise
	 * is_proxy_tracking_request(). Restoring the full array — rather than
	 * unset()-ing the specific keys a test touched — puts back whatever the
	 * WordPress bootstrap actually populated (e.g. REQUEST_URI defaults to
	 * '', not "absent"), and does so from tear_down() so a failed assertion
	 * mid-test cannot skip cleanup and leak state into the next test.
	 *
	 * @var array
	 */
	private $server_snapshot = array();

	/**
	 * Clear the memoized reserved-name list before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->server_snapshot = $_SERVER;
		$this->reset_reserved_property_names();
		$this->reset_pixel_batch_queue();
		$this->reset_cached_ip();
		delete_transient( 'wc_analytics_blog_details' );
	}

	/**
	 * Clear the memoized reserved-name list after each test.
	 *
	 * Runs unconditionally regardless of the test outcome, so a seeded
	 * `woocommerce_store_id` option, the blog-details transient, or the cached
	 * IP address set up for one test (to prove substitution rather than mere
	 * absence, see test_record_client_event_drops_client_supplied_server_properties())
	 * cannot leak into the next test even if an assertion fails first.
	 */
	public function tear_down(): void {
		$_SERVER = $this->server_snapshot;
		$this->reset_reserved_property_names();
		$this->reset_pixel_batch_queue();
		$this->reset_cached_ip();
		delete_transient( 'wc_analytics_blog_details' );
		parent::tear_down();
	}

	/**
	 * The memo persists for the life of the PHP process, so tests must clear it
	 * or the first test's environment leaks into every later one.
	 */
	private function reset_reserved_property_names(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'reserved_property_names' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Pins the effective reserved set. This test exists to fail: adding a
	 * property to get_page_common_properties() or get_server_details() must
	 * force a deliberate decision about whether a client may set it, rather
	 * than silently inheriting protection or silently missing out on it.
	 */
	public function test_reserved_property_names_match_the_documented_set(): void {
		$expected = array(
			// get_session_properties().
			'session_id',
			'landing_page',
			'is_engaged',
			// get_page_common_properties().
			'ui',
			'blog_id',
			'store_id',
			'url',
			'woo_version',
			'wp_version',
			'store_admin',
			'device',
			'store_currency',
			'timezone',
			'is_guest',
			// get_server_details(), minus CLIENT_OVERRIDABLE_PROPERTIES.
			'_via_ua',
			'_via_ip',
			'_via_ref',
			// Identity and envelope.
			'_ui',
			'_ut',
			'_en',
			'browser_type',
		);

		$actual = WC_Analytics_Tracking::get_reserved_property_names();

		sort( $expected );
		sort( $actual );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * The three properties the client is authoritative for must not be
	 * reserved: on the proxy path the server's values describe the /track
	 * request, not the page the event happened on.
	 */
	public function test_client_overridable_properties_are_not_reserved(): void {
		$reserved = WC_Analytics_Tracking::get_reserved_property_names();

		$this->assertNotContains( '_lg', $reserved );
		$this->assertNotContains( '_dl', $reserved );
		$this->assertNotContains( '_dr', $reserved );
	}

	/**
	 * The strip removes reserved names and leaves everything else — including
	 * arbitrary event-specific properties — untouched.
	 */
	public function test_strip_removes_reserved_names_only(): void {
		$stripped = WC_Analytics_Tracking::strip_reserved_properties(
			array(
				'_via_ip'     => '8.8.8.8',
				'store_id'    => 'someone-elses-store',
				'blog_id'     => 12345,
				'session_id'  => 'forged-session',
				'store_admin' => 1,
				'is_guest'    => 0,
				'_ui'         => 'forged-visitor',
				'_lg'         => 'en-GB',
				'_dl'         => 'https://example.com/product/thing',
				'_dr'         => 'https://example.com/',
				'pi'          => 42,
				'pq'          => 2,
			)
		);

		$this->assertSame(
			array(
				'_lg' => 'en-GB',
				'_dl' => 'https://example.com/product/thing',
				'_dr' => 'https://example.com/',
				'pi'  => 42,
				'pq'  => 2,
			),
			$stripped
		);
	}

	/**
	 * Defensive: the REST body is attacker-shaped, so a non-array or empty
	 * value must not fatal.
	 */
	public function test_strip_handles_empty_and_non_array_input(): void {
		$this->assertSame( array(), WC_Analytics_Tracking::strip_reserved_properties( array() ) );
		$this->assertSame( array(), WC_Analytics_Tracking::strip_reserved_properties( 'not-an-array' ) );
		$this->assertSame( array(), WC_Analytics_Tracking::strip_reserved_properties( null ) );
	}

	/**
	 * Read the queued pixel URLs. record_event() queues rather than sends when
	 * the Requests library supports request_multiple(), which it does here.
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
	 * Clear the queued pixel URLs and the cached visitor id, so one test's
	 * cookie cannot leak into the next.
	 */
	private function reset_pixel_batch_queue(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );

		$property = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		$property->setValue( null, array() );

		$visitor = $reflection->getProperty( 'cached_visitor_id' );
		$visitor->setAccessible( true );
		$visitor->setValue( null, null );
	}

	/**
	 * Clear the cached IP address, so a REMOTE_ADDR seeded for one test cannot
	 * leak into the next: get_user_ip_address() memoizes its result for the
	 * life of the PHP process.
	 */
	private function reset_cached_ip(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'cached_ip' );
		$property->setAccessible( true );
		$property->setValue( null, null );
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
	 * A client that posts server-owned properties must not see them reach the
	 * pixel. This is the core of WOOA7S-1803.
	 *
	 * Both `store_id` and `_via_ip` are seeded with a real server-derived value
	 * before the call, so the assertions prove the server's value replaced the
	 * client's forged one, not merely that the forged one is absent. Under
	 * WorDBless, `get_option( 'woocommerce_store_id', null )` is null and
	 * `http_build_query()` drops null values, so an assertNotSame() against an
	 * unseeded environment would pass on absence alone and miss a stripped-but-
	 * not-substituted bug.
	 */
	public function test_record_client_event_drops_client_supplied_server_properties(): void {
		$_COOKIE['tk_ai']       = 'test-visitor-id-1234567890ab';
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
		update_option( 'woocommerce_store_id', 'real-store-id' );
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_client_event(
			'add_to_cart',
			array(
				'_via_ip'  => '8.8.8.8',
				'store_id' => 'someone-elses-store',
				'_ui'      => 'forged-visitor',
				'pi'       => 42,
			)
		);

		$props = $this->get_queued_pixel_props();

		$this->assertSame( '203.0.113.7', $props['_via_ip'] ?? null, '_via_ip must come from the server (REMOTE_ADDR), not the client.' );
		$this->assertSame( 'real-store-id', $props['store_id'] ?? null, 'store_id must come from the server (woocommerce_store_id option), not the client.' );
		$this->assertSame( 'test-visitor-id-1234567890ab', $props['_ui'] ?? null, '_ui must come from the tk_ai cookie.' );
		$this->assertSame( '42', $props['pi'] ?? null, 'Event-specific properties must survive.' );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * The three client-authoritative properties must survive to the pixel,
	 * otherwise proxy-mode page attribution breaks: the server's values would
	 * describe the /track request instead of the page.
	 */
	public function test_record_client_event_keeps_client_authoritative_properties(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_client_event(
			'add_to_cart',
			array(
				'_lg' => 'en-GB',
				'_dl' => 'https://example.com/product/thing',
				'_dr' => 'https://example.com/',
			)
		);

		$props = $this->get_queued_pixel_props();

		$this->assertSame( 'en-GB', $props['_lg'] ?? null );
		$this->assertSame( 'https://example.com/product/thing', $props['_dl'] ?? null );
		$this->assertSame( 'https://example.com/', $props['_dr'] ?? null );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * The trusted path is unchanged: a server-side caller can still set a
	 * property that collides with a common one. Universal and My_Account rely
	 * on record_event() keeping these semantics.
	 */
	public function test_record_event_leaves_trusted_caller_properties_alone(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_event(
			'add_to_cart',
			array( 'store_id' => 'set-by-trusted-caller' )
		);

		$props = $this->get_queued_pixel_props();

		$this->assertSame( 'set-by-trusted-caller', $props['store_id'] ?? null );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Simulates a stale MU-plugin copy: it calls record_event() with no flag,
	 * during a POST to the track endpoint. The guard must strip anyway.
	 */
	public function test_record_event_strips_during_a_proxy_request(): void {
		$_COOKIE['tk_ai']           = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD']  = 'POST';
		$_SERVER['REQUEST_URI']     = '/wp-json/woocommerce-analytics/v1/track';
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_event(
			'add_to_cart',
			array( 'store_id' => 'someone-elses-store' )
		);

		$props = $this->get_queued_pixel_props();

		$this->assertNotSame( 'someone-elses-store', $props['store_id'] ?? null );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * The over-stripping guard. If the path or method test is ever loosened,
	 * this is what fails — trusted server-side callers must keep their
	 * properties on every request that is not a proxy POST.
	 *
	 * @dataProvider non_proxy_request_provider
	 *
	 * @param string $method Request method.
	 * @param string $uri    Request URI.
	 */
	public function test_record_event_does_not_strip_outside_a_proxy_request( string $method, string $uri ): void {
		$_COOKIE['tk_ai']          = 'test-visitor-id-1234567890ab';
		$_SERVER['REQUEST_METHOD'] = $method;
		$_SERVER['REQUEST_URI']    = $uri;
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_event(
			'add_to_cart',
			array( 'store_id' => 'set-by-trusted-caller' )
		);

		$props = $this->get_queued_pixel_props();

		$this->assertSame( 'set-by-trusted-caller', $props['store_id'] ?? null );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Requests that must not trigger the guard.
	 *
	 * The `?rest_route=` case is load-bearing, not incidental: on a
	 * plain-permalink site `rest_url()` produces exactly this shape, whose
	 * parsed path is `/`, so the guard correctly does not match it — and
	 * neither does `WooCommerceAnalyticsProxySpeed::is_proxy_request()` in the
	 * MU-plugin template. The two copies agreeing here is what keeps a stale
	 * template from intercepting a request this guard would not recognise. On
	 * such sites `record_client_event()` — not this guard — is the only thing
	 * stripping reserved properties.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function non_proxy_request_provider(): array {
		return array(
			'GET to the track path'          => array( 'GET', '/wp-json/woocommerce-analytics/v1/track' ),
			'POST to the Store API'          => array( 'POST', '/wp-json/wc/store/v1/checkout' ),
			'POST to a lookalike suffix'     => array( 'POST', '/wp-json/other/woocommerce-analytics/v1/tracking' ),
			'POST to the site root'          => array( 'POST', '/' ),
			'POST with rest_route query var' => array( 'POST', '/?rest_route=/woocommerce-analytics/v1/track' ),
		);
	}

	/**
	 * Shapes that must still match, so the safety net is not defeated by a
	 * query string, a trailing slash, or a subdirectory install.
	 *
	 * @dataProvider proxy_request_shape_provider
	 *
	 * @param string $uri Request URI.
	 */
	public function test_is_proxy_tracking_request_matches_real_world_shapes( string $uri ): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = $uri;

		$this->assertTrue( WC_Analytics_Tracking::is_proxy_tracking_request(), $uri );
	}

	/**
	 * URIs that must match.
	 *
	 * Note what is deliberately absent: the genuine `?rest_route=` query-var
	 * form (e.g. `/?rest_route=/woocommerce-analytics/v1/track`, what
	 * `rest_url()` produces on a plain-permalink site) does NOT match this
	 * guard — its parsed path is just `/`. That is covered as a non-matching
	 * case in non_proxy_request_provider(), not here.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function proxy_request_shape_provider(): array {
		return array(
			'plain'                                    => array( '/wp-json/woocommerce-analytics/v1/track' ),
			'trailing slash'                           => array( '/wp-json/woocommerce-analytics/v1/track/' ),
			'query string'                             => array( '/wp-json/woocommerce-analytics/v1/track?_wpnonce=abc123' ),
			'subdirectory'                             => array( '/shop/wp-json/woocommerce-analytics/v1/track' ),
			'index.php-prefixed pretty-permalink form' => array( '/index.php/wp-json/woocommerce-analytics/v1/track' ),
		);
	}

	/**
	 * The character restriction on the path must reject anything outside the
	 * allowed set, matching the MU-plugin template's is_proxy_request(). This
	 * is the one piece of the "kept in step with the template" claim that was
	 * previously covered only by inspection, not by an assertion.
	 *
	 * The disallowed '%' sits mid-path, not at the end, on purpose: the path
	 * still ends with the exact proxy suffix, so the suffix comparison alone
	 * would accept this request. Only the character-restriction check can
	 * reject it. A trailing disallowed character (e.g. a suffix of
	 * "/track%20") would also break the suffix match by itself, so it would
	 * return false regardless of whether the character check exists — that
	 * shape cannot isolate the regex, and must not be used here.
	 */
	public function test_is_proxy_tracking_request_rejects_disallowed_characters(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['REQUEST_URI']    = '/wp%20json/woocommerce-analytics/v1/track';

		$this->assertFalse( WC_Analytics_Tracking::is_proxy_tracking_request() );
	}

	/**
	 * A callback that assigns unconditionally beats a client value of the same
	 * name. This is the pattern the filter docblock tells extensions to use.
	 */
	public function test_filter_callback_assigning_unconditionally_beats_the_client(): void {
		$callback = function ( $props ) {
			$props['partner_tier'] = 'gold';
			return $props;
		};
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$props = WC_Analytics_Tracking::get_properties(
			'woocommerceanalytics_add_to_cart',
			array( 'partner_tier' => 'forged' ),
			true
		);

		remove_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$this->assertSame( 'gold', $props['partner_tier'] );
	}

	/**
	 * The known limitation, asserted so it is a recorded decision rather than an
	 * assumption: a callback that defers to an existing value loses to the
	 * client, because the reserved set cannot cover names the filter invents.
	 * If this ever starts passing, the limitation has been closed and the spec
	 * needs updating.
	 */
	public function test_filter_callback_deferring_to_an_existing_value_loses_to_the_client(): void {
		$callback = function ( $props ) {
			$props['partner_tier'] = isset( $props['partner_tier'] ) ? $props['partner_tier'] : 'gold';
			return $props;
		};
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$props = WC_Analytics_Tracking::get_properties(
			'woocommerceanalytics_add_to_cart',
			array( 'partner_tier' => 'forged' ),
			true
		);

		remove_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$this->assertSame( 'forged', $props['partner_tier'], 'Known limitation: see the spec.' );
	}

	/**
	 * The third argument tells a callback whether the properties it is looking
	 * at came from an untrusted client, which is the only way it can know to
	 * assign unconditionally.
	 */
	public function test_filter_receives_the_client_supplied_flag(): void {
		$seen     = array();
		$callback = function ( $props, $event_name, $is_client_supplied ) use ( &$seen ) {
			$seen[] = $is_client_supplied;
			return $props;
		};
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback, 10, 3 );

		WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_add_to_cart', array(), true );
		WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_add_to_cart', array(), false );

		remove_filter( 'jetpack_woocommerce_analytics_event_props', $callback, 10 );

		$this->assertSame( array( true, false ), $seen );
	}
}
