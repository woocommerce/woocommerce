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
	 * Globals this class overwrites, captured before the first change so
	 * `tear_down()` can put back exactly what it found. `null` records a key
	 * that was absent.
	 *
	 * @var array<string, mixed>
	 */
	private $original_globals = array();

	/**
	 * Send every request through the poisoned headers and a session cookie, and
	 * clear the cached IP so each test resolves them afresh.
	 */
	public function set_up(): void {
		parent::set_up();

		global $wp_query;

		$this->original_globals = array(
			'cookie'      => $_COOKIE['woocommerceanalytics_session'] ?? null,
			'post'        => $GLOBALS['post'] ?? null,
			'is_search'   => $wp_query->is_search,
			'search_term' => $wp_query->get( 's' ),
		);

		foreach ( self::POISONED_HEADERS as $key => $value ) {
			$this->original_globals[ 'server:' . $key ] = $_SERVER[ $key ] ?? null;

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
	 * Put back every global this class touched, so a later test sees the state
	 * it would have seen had this class not run.
	 */
	public function tear_down(): void {
		global $wp_query;

		// Superglobals cannot be passed by reference, so each is restored inline.
		foreach ( array_keys( self::POISONED_HEADERS ) as $key ) {
			if ( $this->original_globals[ 'server:' . $key ] === null ) {
				unset( $_SERVER[ $key ] );
			} else {
				$_SERVER[ $key ] = $this->original_globals[ 'server:' . $key ];
			}
		}

		if ( $this->original_globals['cookie'] === null ) {
			unset( $_COOKIE['woocommerceanalytics_session'] );
		} else {
			$_COOKIE['woocommerceanalytics_session'] = $this->original_globals['cookie'];
		}

		if ( $this->original_globals['post'] === null ) {
			unset( $GLOBALS['post'] );
		} else {
			$GLOBALS['post'] = $this->original_globals['post'];
		}

		$wp_query->is_search = $this->original_globals['is_search'];
		$wp_query->set( 's', $this->original_globals['search_term'] );

		$this->original_globals = array();

		$this->reset_cached_ip();
		$this->reset_event_queue();

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
	 * Test that a breadcrumb title cannot inflate the page. Titles come from
	 * `post_title`, which core does not bound.
	 */
	public function test_page_output_caps_long_breadcrumb_titles(): void {
		$breadcrumbs = $this->render_breadcrumbs_for_title( str_repeat( 'A', 5000 ) );

		$this->assertSame(
			array( str_repeat( 'A', 199 ) . '…' ),
			$breadcrumbs,
			'A breadcrumb title should be capped before it reaches cacheable page output.'
		);
	}

	/**
	 * Test that the cap counts characters rather than bytes. A byte-wise cut
	 * splits the final UTF-8 sequence, which `wp_json_encode()` silently rewrites
	 * to a replacement character.
	 */
	public function test_page_output_caps_breadcrumb_titles_by_character(): void {
		$breadcrumbs = $this->render_breadcrumbs_for_title( str_repeat( '日', 250 ) );

		$this->assertSame(
			array( str_repeat( '日', 199 ) . '…' ),
			$breadcrumbs,
			'A breadcrumb title should be capped by character, not by byte.'
		);
	}

	/**
	 * Test that a title sitting exactly on the limit is not truncated.
	 */
	public function test_page_output_keeps_breadcrumb_titles_at_the_limit_intact(): void {
		$title = str_repeat( 'A', 200 );

		$this->assertSame(
			array( $title ),
			$this->render_breadcrumbs_for_title( $title ),
			'A title exactly at the limit should be sent unchanged.'
		);
	}

	/**
	 * Test that a breadcrumb title short enough to keep is left untouched.
	 */
	public function test_page_output_keeps_short_breadcrumb_titles_intact(): void {
		$title = 'Perfectly Ordinary Product Name';

		$this->assertSame(
			array( $title ),
			$this->render_breadcrumbs_for_title( $title ),
			'Ordinary titles should be sent unchanged.'
		);
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
	 * Test that a long search term is capped before it is queued for the page. The
	 * term reaches the pixel twice — as `search_query` and inside the browser's own
	 * `_dl` — so an uncapped term can push the URL past what a server will accept.
	 *
	 * 1500 characters is deliberately under the 1600 bytes above which
	 * `WP_Query::parse_query()` blanks `s`, so this is an input a request can
	 * actually produce.
	 */
	public function test_queued_search_event_caps_long_search_query(): void {
		$this->assertSame(
			str_repeat( 'A', 199 ) . '…',
			$this->capture_search_event( str_repeat( 'A', 1500 ) ),
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
	 * Render the page payload for a post with the given title.
	 *
	 * @param string $title The post title the breadcrumb trail is built from.
	 * @return array The decoded breadcrumb titles.
	 */
	private function render_breadcrumbs_for_title( string $title ): array {
		$GLOBALS['post'] = new \WP_Post(
			(object) array(
				'ID'         => 1,
				'post_title' => $title,
				'post_type'  => 'post',
				'filter'     => 'raw',
			)
		);

		return $this->get_rendered_breadcrumbs( $this->render_analytics_data() );
	}

	/**
	 * Pull the breadcrumb trail back out of the rendered markup.
	 *
	 * @param string $output The markup written by `inject_analytics_data()`.
	 * @return array The decoded breadcrumb titles.
	 */
	private function get_rendered_breadcrumbs( string $output ): array {
		$assignment = 'wcAnalytics.breadcrumbs = ';

		$start = strpos( $output, $assignment );
		$this->assertNotFalse( $start, 'The rendered markup should assign wcAnalytics.breadcrumbs.' );

		$start += strlen( $assignment );
		$end    = strpos( $output, "\n", $start );
		$this->assertNotFalse( $end, 'The breadcrumb assignment should end with a line break.' );

		// Take the rest of the line and drop the statement's trailing semicolon.
		$json = rtrim( trim( substr( $output, $start, $end - $start ) ), ';' );

		return (array) json_decode( $json, true );
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
