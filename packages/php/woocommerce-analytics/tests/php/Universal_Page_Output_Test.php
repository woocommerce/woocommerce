<?php
/**
 * Tests for the analytics payload injected into front-end page HTML.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests that the cacheable page output carries no request-derived properties.
 *
 * `inject_analytics_data()` writes into front-end HTML, which CDNs cache and
 * replay to later visitors. Request headers such as `Referer` and
 * `Cf-Connecting-Ip` are not part of the cache key, so any property derived
 * from them that reaches the page body is served back to everyone who hits
 * the cached copy — one unauthenticated request would otherwise decide what
 * every subsequent visitor reports as their own IP, user agent and referrer.
 */
class Universal_Page_Output_Test extends BaseTestCase {

	/**
	 * Properties that `get_server_details()` derives from the current request.
	 * None of them may appear in cacheable page output.
	 *
	 * @var string[]
	 */
	private const REQUEST_DERIVED_PROPERTIES = array( '_via_ip', '_via_ua', '_via_ref', '_dr', '_dl', '_lg' );

	/**
	 * Properties read from the visitor's session cookie. The client owns this
	 * cookie and reads it itself, so these must not reach cacheable page output
	 * either.
	 *
	 * @var string[]
	 */
	private const SESSION_PROPERTIES = array( 'session_id', 'landing_page', 'is_engaged' );

	/**
	 * Request header values used to poison the page, keyed by superglobal key.
	 * Each is distinctive enough to grep the whole response body for.
	 *
	 * @var array<string, string>
	 */
	private const POISONED_HEADERS = array(
		'HTTP_REFERER'          => 'https://attacker.example/poisoned-referrer',
		'HTTP_CF_CONNECTING_IP' => '203.0.113.199',
		'HTTP_USER_AGENT'       => 'PoisonedUserAgent/1.0',
		'HTTP_ACCEPT_LANGUAGE'  => 'zz-ZZ',
	);

	/**
	 * A session cookie value carrying another visitor's session.
	 *
	 * @var string
	 */
	private const POISONED_SESSION_ID = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

	/**
	 * Send every request through the poisoned headers and a session cookie, and
	 * clear the cached IP so each test resolves them afresh.
	 */
	public function set_up(): void {
		parent::set_up();

		foreach ( self::POISONED_HEADERS as $key => $value ) {
			$_SERVER[ $key ] = $value;
		}

		$_COOKIE['woocommerceanalytics_session'] = rawurlencode(
			(string) wp_json_encode(
				array(
					'session_id'   => self::POISONED_SESSION_ID,
					'landing_page' => '["Someone else\'s landing page"]',
					'is_engaged'   => true,
				)
			)
		);

		$this->reset_cached_ip();
	}

	/**
	 * Remove the poisoned headers and cookie so they cannot leak into other
	 * test classes.
	 */
	public function tear_down(): void {
		foreach ( array_keys( self::POISONED_HEADERS ) as $key ) {
			unset( $_SERVER[ $key ] );
		}

		unset( $_COOKIE['woocommerceanalytics_session'] );

		$this->reset_cached_ip();
		$this->reset_event_queue();

		global $wp_query;
		$wp_query->is_search = false;
		$wp_query->set( 's', '' );

		parent::tear_down();
	}

	/**
	 * Test that the visitor's session properties stay out of the page output.
	 *
	 * The client reads the same cookie through its own SessionManager, so these
	 * are redundant in the markup — and the markup is cached, which would pin
	 * one visitor's session identifier onto everyone served the cached copy.
	 */
	public function test_page_output_omits_session_properties(): void {
		$output = $this->render_analytics_data();

		foreach ( self::SESSION_PROPERTIES as $property ) {
			$this->assertStringNotContainsString(
				'"' . $property . '"',
				$output,
				sprintf( 'The "%s" session property must not reach cacheable page output.', $property )
			);
		}

		$this->assertStringNotContainsString(
			self::POISONED_SESSION_ID,
			$output,
			'A session identifier from the request cookie must not be reflected into cacheable page output.'
		);
	}

	/**
	 * Test that the server-fired path keeps the session properties. It runs on
	 * uncached requests — including the proxy tracking endpoint — where the
	 * cookie genuinely belongs to the visitor being recorded.
	 */
	public function test_server_fired_properties_retain_session_details(): void {
		$properties = WC_Analytics_Tracking::get_common_properties();

		foreach ( self::SESSION_PROPERTIES as $property ) {
			$this->assertArrayHasKey(
				$property,
				$properties,
				sprintf( 'The server-fired path still needs the "%s" property.', $property )
			);
		}

		$this->assertSame( self::POISONED_SESSION_ID, $properties['session_id'] );
		$this->assertTrue( $properties['is_engaged'] );
	}

	/**
	 * Test that no request-derived property name reaches the page output.
	 */
	public function test_page_output_omits_request_derived_property_names(): void {
		$output = $this->render_analytics_data();

		foreach ( self::REQUEST_DERIVED_PROPERTIES as $property ) {
			$this->assertStringNotContainsString(
				'"' . $property . '"',
				$output,
				sprintf(
					'The "%s" property is derived from the current request and must not reach cacheable page output.',
					$property
				)
			);
		}
	}

	/**
	 * Test that no value taken from a request header reaches the page output.
	 *
	 * This is the poisoning check: headers outside the CDN cache key must not
	 * influence the cached response body at all.
	 */
	public function test_page_output_omits_request_header_values(): void {
		$output = $this->render_analytics_data();

		foreach ( self::POISONED_HEADERS as $key => $value ) {
			$this->assertStringNotContainsString(
				$value,
				$output,
				sprintf( 'The %s request header must not be reflected into cacheable page output.', $key )
			);
		}
	}

	/**
	 * Test that store-level properties survive, so the assertions above cannot
	 * pass merely because the payload went empty.
	 */
	public function test_page_output_retains_store_properties(): void {
		$output = $this->render_analytics_data();

		foreach ( array( 'timezone', 'wp_version', 'store_currency' ) as $property ) {
			$this->assertStringContainsString(
				'"' . $property . '"',
				$output,
				sprintf( 'The store-level "%s" property should still be sent to the client.', $property )
			);
		}
	}

	/**
	 * Test that a breadcrumb title cannot inflate the page.
	 *
	 * On search pages the breadcrumb trail embeds the raw search term, so a
	 * long request can otherwise push an arbitrary amount of text into every
	 * copy of the cached response.
	 */
	public function test_page_output_caps_long_breadcrumb_titles(): void {
		$GLOBALS['post'] = new \WP_Post(
			(object) array(
				'ID'         => 1,
				'post_title' => str_repeat( 'A', 5000 ),
				'post_type'  => 'post',
				'filter'     => 'raw',
			)
		);

		$breadcrumbs = $this->get_rendered_breadcrumbs( $this->render_analytics_data() );

		unset( $GLOBALS['post'] );

		$this->assertNotEmpty( $breadcrumbs, 'The breadcrumb trail should still be rendered.' );

		foreach ( $breadcrumbs as $title ) {
			$this->assertLessThanOrEqual(
				200,
				mb_strlen( $title ),
				'A breadcrumb title should be capped before it reaches cacheable page output.'
			);
		}
	}

	/**
	 * Test that a breadcrumb title short enough to keep is left untouched.
	 */
	public function test_page_output_keeps_short_breadcrumb_titles_intact(): void {
		$title = 'Perfectly Ordinary Product Name';

		$GLOBALS['post'] = new \WP_Post(
			(object) array(
				'ID'         => 1,
				'post_title' => $title,
				'post_type'  => 'post',
				'filter'     => 'raw',
			)
		);

		$breadcrumbs = $this->get_rendered_breadcrumbs( $this->render_analytics_data() );

		unset( $GLOBALS['post'] );

		$this->assertSame( array( $title ), $breadcrumbs, 'Ordinary titles should be sent unchanged.' );
	}

	/**
	 * Test that proxy mode still sends an empty payload. It reaches the same
	 * cacheable markup, and the pixel is fired server-side from the visitor's
	 * own uncached request instead.
	 */
	public function test_page_output_is_empty_under_proxy_tracking(): void {
		add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		$output = $this->render_analytics_data();

		remove_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		$this->assertStringContainsString(
			'wcAnalytics.commonProps = [];',
			$output,
			'Proxy mode should send no common properties to the client.'
		);
	}

	/**
	 * Test that the server-fired pixel path keeps its request-derived
	 * properties. That path runs on uncached requests and is the only place
	 * where pixel.wp.com can learn the visitor's real IP, user agent and
	 * referrer, so narrowing the page output must not narrow it too.
	 */
	public function test_server_fired_properties_retain_request_details(): void {
		$properties = WC_Analytics_Tracking::get_common_properties();

		foreach ( self::REQUEST_DERIVED_PROPERTIES as $property ) {
			$this->assertArrayHasKey(
				$property,
				$properties,
				sprintf( 'The server-fired pixel path still needs the "%s" property.', $property )
			);
		}

		$this->assertSame( self::POISONED_HEADERS['HTTP_CF_CONNECTING_IP'], $properties['_via_ip'] );
		$this->assertSame( self::POISONED_HEADERS['HTTP_USER_AGENT'], $properties['_via_ua'] );
		$this->assertSame( self::POISONED_HEADERS['HTTP_REFERER'], $properties['_via_ref'] );
	}

	/**
	 * Test that a long search term is capped before it is queued for the page.
	 *
	 * The search event is assembled server-side and fired by the client, so the
	 * term travels through the markup. It also reaches the pixel twice — once as
	 * `search_query` and again inside the browser's own `_dl` — so an uncapped
	 * term can push the pixel URL past what a server will accept, losing the
	 * event outright.
	 */
	public function test_queued_search_event_caps_long_search_query(): void {
		$search_query = $this->capture_search_event( str_repeat( 'A', 5000 ) );

		$this->assertLessThanOrEqual(
			200,
			mb_strlen( $search_query ),
			'A search term should be capped before it is queued into page output.'
		);
	}

	/**
	 * Test that an ordinary search term is queued unchanged.
	 */
	public function test_queued_search_event_keeps_ordinary_search_query_intact(): void {
		$term = 'blue cotton t-shirt';

		$this->assertSame(
			$term,
			$this->capture_search_event( $term ),
			'Ordinary search terms should be recorded unchanged.'
		);
	}

	/**
	 * Run a search request through `capture_search_query()` and return the
	 * `search_query` property it queued for the page.
	 *
	 * @param string $term The search term the visitor requested.
	 * @return string The queued search query.
	 */
	private function capture_search_event( string $term ): string {
		global $wp_query;

		$wp_query->is_search = true;
		$wp_query->set( 's', $term );

		$universal = new Universal();
		$universal->capture_search_query();

		$queue = WC_Analytics_Tracking::get_event_queue();
		$this->assertNotEmpty( $queue, 'A search event should have been queued.' );

		$event = end( $queue );
		$this->assertSame( 'search', $event['eventName'], 'The queued event should be the search event.' );

		return (string) ( $event['props']['search_query'] ?? '' );
	}

	/**
	 * Render the injected analytics script and return its markup.
	 *
	 * @return string The markup written by `inject_analytics_data()`.
	 */
	private function render_analytics_data(): string {
		$universal = new Universal();

		ob_start();
		$universal->inject_analytics_data();
		return (string) ob_get_clean();
	}

	/**
	 * Pull the breadcrumb trail back out of the rendered markup.
	 *
	 * @param string $output The markup written by `inject_analytics_data()`.
	 * @return array The decoded breadcrumb titles.
	 */
	private function get_rendered_breadcrumbs( string $output ): array {
		$matched = preg_match( '/wcAnalytics\.breadcrumbs = (.+);$/m', $output, $matches );

		$this->assertSame( 1, $matched, 'The rendered markup should assign wcAnalytics.breadcrumbs.' );

		return (array) json_decode( $matches[1], true );
	}

	/**
	 * Clear the `event_queue` static so a queued event cannot leak into another
	 * test's rendered output.
	 */
	private function reset_event_queue(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'event_queue' );
		$property->setAccessible( true );
		$property->setValue( null, array() );
	}

	/**
	 * Clear the `cached_ip` static so a test's headers are resolved rather than
	 * a value another test left behind.
	 */
	private function reset_cached_ip(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'cached_ip' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}
}
