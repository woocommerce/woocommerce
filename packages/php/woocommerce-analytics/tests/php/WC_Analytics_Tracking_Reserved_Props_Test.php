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
	 * Restoring the full array — rather than unset()-ing the specific keys a
	 * test touched — puts back whatever the WordPress bootstrap actually
	 * populated, and does so from tear_down() so a failed assertion mid-test
	 * cannot skip cleanup and leak state into the next test.
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
	 * Reset the process-global state WorDBless does not.
	 *
	 * Options and transients are cleared by `BaseTestCase`, so the seeded
	 * `woocommerce_store_id` is already handled. The memoized reserved-name list,
	 * the pixel queue and the cached IP are static properties WorDBless never
	 * sees, and they would otherwise carry one test's environment into the next.
	 * Runs unconditionally, so a failed assertion cannot skip the reset.
	 */
	public function tear_down(): void {
		$_SERVER = $this->server_snapshot;
		unset( $_COOKIE['tk_ai'] );
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
	 * property to get_session_properties(), get_page_common_properties() or
	 * get_server_details() must force a deliberate decision about whether a
	 * client may set it, rather than silently inheriting protection or silently
	 * missing out on it.
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
			'_ts',
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
			$seen[] = array( $event_name, $is_client_supplied );
			return $props;
		};
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback, 10, 3 );

		WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_add_to_cart', array(), true );
		WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', array(), false );

		remove_filter( 'jetpack_woocommerce_analytics_event_props', $callback, 10 );

		$this->assertSame(
			array(
				array( 'woocommerceanalytics_add_to_cart', true ),
				array( 'woocommerceanalytics_product_view', false ),
			),
			$seen
		);
	}

	/**
	 * A callback that defers to an existing value hands a *reserved* property
	 * straight back to the client that supplied it. The strip runs before the
	 * filter, so it cannot see this; get_properties() re-asserts the server's
	 * values afterwards. Contrast with
	 * test_filter_callback_deferring_to_an_existing_value_loses_to_the_client(),
	 * which covers a name the filter invents — still the client's to win.
	 */
	public function test_filter_callback_cannot_hand_a_reserved_property_back_to_the_client(): void {
		update_option( 'woocommerce_store_id', 'real-store-id' );

		$callback = function ( $props ) {
			$props['store_id'] = isset( $props['store_id'] ) ? $props['store_id'] : 'unused';
			return $props;
		};
		add_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$props = WC_Analytics_Tracking::get_properties(
			'woocommerceanalytics_add_to_cart',
			array( 'store_id' => 'someone-elses-store' ),
			true
		);

		remove_filter( 'jetpack_woocommerce_analytics_event_props', $callback );

		$this->assertSame( 'real-store-id', $props['store_id'] );
	}

	/**
	 * The trusted path must keep its escape hatch: a server-side caller can still
	 * set a property that collides with a common one, and the post-filter
	 * re-assertion must not take that away.
	 */
	public function test_reserved_properties_are_not_re_asserted_for_trusted_callers(): void {
		update_option( 'woocommerce_store_id', 'real-store-id' );

		$props = WC_Analytics_Tracking::get_properties(
			'woocommerceanalytics_add_to_cart',
			array( 'store_id' => 'set-by-trusted-caller' ),
			false
		);

		$this->assertSame( 'set-by-trusted-caller', $props['store_id'] );
	}

	/**
	 * `_ts` is in $required_properties and in RESERVED_IDENTITY_PROPERTIES, so a
	 * client cannot forge the event timestamp at either layer.
	 */
	public function test_client_cannot_forge_the_event_timestamp(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';

		WC_Analytics_Tracking::record_client_event(
			'add_to_cart',
			array(
				'_ts' => '1',
				'pi'  => 42,
			)
		);

		$props = $this->get_queued_pixel_props();

		$this->assertMatchesRegularExpression( '/^\d{13}$/', (string) ( $props['_ts'] ?? '' ), '_ts must be the server timestamp.' );
	}

	/**
	 * A JSON array survives the consumers' truthiness check and reaches
	 * `PREFIX . $event_name`, where PHP writes a warning to the log on an
	 * unauthenticated request. `failOnWarning` makes that warning fail the test.
	 *
	 * @dataProvider unusable_client_event_name_provider
	 *
	 * @param mixed $event_name Name a client could post.
	 */
	public function test_client_events_with_an_unusable_name_are_dropped( $event_name ): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		$result = WC_Analytics_Tracking::record_client_event( $event_name, array( 'pi' => 42 ) );

		$this->assertTrue( is_wp_error( $result ), 'Reporting success for an event that produced no pixel is what hides the loss.' );
		$this->assertSame( 'invalid_event_name', $result->get_error_code() );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel may be queued for an unusable event name.' );
	}

	/**
	 * Names a client could post that cannot become an `_en` value.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function unusable_client_event_name_provider(): array {
		return array(
			'array'      => array( array( 'product_view' ) ),
			'nested map' => array( array( 'name' => 'product_view' ) ),
			'empty'      => array( '' ),
		);
	}

	/**
	 * The template must record through the untrusted-client entry point. Nothing
	 * in the suite executes the template, so asserting on its source text is
	 * crude, but it is the only thing standing behind that requirement.
	 */
	public function test_mu_plugin_template_stays_in_step_with_the_package(): void {
		$template = file_get_contents(
			dirname( __DIR__, 2 ) . '/src/mu-plugin/woocommerce-analytics-proxy-speed-module-template.php'
		);

		$this->assertStringContainsString(
			'::record_client_event(',
			$template,
			'The template must record through the untrusted-client entry point.'
		);
		$this->assertStringNotContainsString(
			'::record_event(',
			$template,
			'The template must not call the trusted entry point.'
		);
	}
}
