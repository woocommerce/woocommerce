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
		unset( $_COOKIE['tk_ai'], $_COOKIE['woocommerceanalytics_session'] );
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
	 * Pass whether properties came from an untrusted client to filter callbacks.
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
	 * Reassert server-owned values after filters run on client properties.
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
	 * Allow trusted callers to override common properties.
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
	 * Use the server timestamp for client events.
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
	 * Truncate oversized client values.
	 */
	public function test_client_property_values_are_capped(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array(
				'pn'    => str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH + 100 ),
				'short' => 'kept',
			)
		);

		$this->assertSame( WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH, mb_strlen( $sanitized['pn'] ) );
		$this->assertStringEndsWith( '…', $sanitized['pn'] );
		$this->assertSame( 'kept', $sanitized['short'], 'Values within the limit must be untouched.' );
	}

	/**
	 * Limit client properties per event.
	 */
	public function test_client_property_count_is_capped(): void {
		$properties = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT + 25; $i++ ) {
			$properties[ 'p' . $i ] = $i;
		}

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );

		$this->assertCount( WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT, $sanitized );
	}

	/**
	 * Prevent nested arrays from generating warnings while flattening values.
	 */
	public function test_nested_client_arrays_do_not_reach_the_flattening_step(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array( 'foo' => array( array( 1, 2 ), 'ok' ) )
		);

		$this->assertSame( array( '', 'ok' ), $sanitized['foo'] );
	}

	/**
	 * Limit client array members before flattening them.
	 */
	public function test_client_array_member_count_is_capped(): void {
		$members = array_fill( 0, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS + 25, 'abcdefghij' );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pc' => $members ) );

		$this->assertCount( WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS, $sanitized['pc'] );

		$props = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', $sanitized, true );

		$this->assertLessThan(
			2000,
			strlen( $props['pc'] ),
			'The flattened value is what reaches the pixel URL, so the cap must survive flattening.'
		);
	}

	/**
	 * Keep capped indexed arrays indexed so they continue to flatten correctly.
	 */
	public function test_capped_arrays_still_flatten_with_implode(): void {
		$members = array_fill( 0, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS + 5, 'a' );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pc' => $members ) );
		$props     = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', $sanitized, true );

		$this->assertSame(
			rawurlencode( implode( ',', array_fill( 0, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS, 'a' ) ) ),
			$props['pc']
		);
	}

	/**
	 * Keep client payloads within the pixel URL limit.
	 */
	public function test_client_payload_total_is_capped( string $character = 'a' ): void {
		$properties = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT; $i++ ) {
			$properties[ 'p' . $i ] = str_repeat( $character, WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		}

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );

		$this->assertNotEmpty( $sanitized, 'The budget drops the tail, it does not empty the event.' );

		// Verify the final URL, not just the budget constant.
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$props            = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', $sanitized, true );

		$this->assertLessThanOrEqual(
			WC_Analytics_Tracking::MAX_PIXEL_URL_LENGTH,
			strlen( Pixel_Builder::build_tracks_url( $props ) )
		);

		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Measure encoded bytes rather than character counts.
	 *
	 * @dataProvider expensive_character_provider
	 *
	 * @param string $character One character whose encoded form is longer than itself.
	 */
	public function test_client_payload_budget_counts_encoded_bytes( string $character ): void {
		$this->test_client_payload_total_is_capped( $character );
	}

	/**
	 * Characters that cost more in the URL than they do in the payload.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function expensive_character_provider(): array {
		return array(
			'percent' => array( '%' ),
			'CJK'     => array( '漢' ),
			'emoji'   => array( '😀' ),
			'space'   => array( ' ' ),
			'tilde'   => array( '~' ),
		);
	}

	/**
	 * Include associative-array keys when measuring payload size.
	 */
	public function test_associative_array_keys_are_charged_to_the_budget(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';

		$properties = array();
		for ( $p = 0; $p < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT; $p++ ) {
			$members = array();
			for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS; $i++ ) {
				$members[ str_repeat( 'k', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) . $i ] = 'v';
			}
			$properties[ 'p' . $p ] = $members;
		}

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );
		$props     = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', $sanitized, true );

		$this->assertLessThanOrEqual(
			WC_Analytics_Tracking::MAX_PIXEL_URL_LENGTH,
			strlen( Pixel_Builder::build_tracks_url( $props ) )
		);

		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Do not charge the budget for properties that are dropped.
	 */
	public function test_a_dropped_property_does_not_spend_the_budget(): void {
		// Long names ensure this exercises each dropped key's cost.
		$properties = array();
		for ( $i = 0; $i < 40; $i++ ) {
			$name                = str_repeat( 'k', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH - 12 ) . $i;
			$properties[ $name ] = str_repeat( 'y', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		}
		$properties['last'] = 'short';

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );

		$this->assertSame( 'short', $sanitized['last'] ?? null, 'A property that fits must not be refused for a dropped one.' );
	}

	/**
	 * Truncate multibyte values without splitting characters.
	 *
	 * @dataProvider multibyte_value_provider
	 *
	 * @param string $character One multibyte character.
	 */
	public function test_value_cap_counts_characters_not_bytes( string $character ): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array( 'pn' => str_repeat( $character, WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH + 50 ) )
		);

		$this->assertLessThan(
			WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH + 50,
			mb_strlen( $sanitized['pn'] ),
			'An over-cap value must come back shorter.'
		);
		$this->assertStringEndsWith( '…', $sanitized['pn'] );
		$this->assertSame( $sanitized['pn'], mb_convert_encoding( $sanitized['pn'], 'UTF-8', 'UTF-8' ), 'The cut must not split a character.' );
	}

	/**
	 * Provide multibyte characters for truncation tests.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function multibyte_value_provider(): array {
		return array(
			'CJK'    => array( '漢' ),
			'emoji'  => array( '😀' ),
			'accent' => array( 'é' ),
		);
	}

	/**
	 * Keep values at the length limit.
	 */
	public function test_a_value_at_the_length_limit_is_untouched(): void {
		$exact = str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pn' => $exact ) );

		$this->assertSame( $exact, $sanitized['pn'] );
	}

	/**
	 * Keep oversized arrays by removing trailing members.
	 */
	public function test_oversized_arrays_lose_members_not_the_property(): void {
		$members = array_fill( 0, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS, str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pc' => $members ) );

		$this->assertArrayHasKey( 'pc', $sanitized, 'The property must survive with fewer members.' );
		$this->assertLessThan( count( $members ), count( $sanitized['pc'] ) );
	}

	/**
	 * Do not queue pixel URLs that exceed the final URL limit.
	 */
	public function test_oversized_pixel_urls_are_not_fired(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		$result = WC_Analytics_Tracking::record_event(
			'add_to_cart',
			array( 'pn' => str_repeat( 'a', WC_Analytics_Tracking::MAX_PIXEL_URL_LENGTH * 2 ) )
		);

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'pixel_too_long', $result->get_error_code() );
		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'Nothing may be queued.' );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Keep typical client payloads unchanged.
	 */
	public function test_a_realistic_client_payload_is_not_capped(): void {
		$properties = array(
			'pi'  => 731,
			'pn'  => 'Some Reasonably Long Product Name With Words',
			'pt'  => 'simple',
			'pc'  => array( 'Clothing', 'Shirts', 'Sale' ),
			'pp'  => 115.81,
			'_lg' => 'en-GB',
			'_dl' => 'https://example.com/product/some-reasonably-long-product-slug/?utm_source=x',
			'_dr' => 'https://example.com/shop/page/3/',
		);

		$this->assertSame( $properties, WC_Analytics_Tracking::sanitize_client_properties( $properties ) );
	}

	/**
	 * Apply bounds when recording a client event.
	 */
	public function test_record_client_event_actually_applies_the_bounds(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		WC_Analytics_Tracking::record_client_event(
			'add_to_cart',
			array(
				'pn'        => str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH + 100 ),
				'Uppercase' => 'dropped by the name check',
			)
		);

		$props = $this->get_queued_pixel_props();

		$this->assertSame( WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH, mb_strlen( $props['pn'] ?? '' ) );
		$this->assertArrayNotHasKey( 'Uppercase', $props );

		$this->reset_pixel_batch_queue();
		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Preserve scalar value types.
	 */
	public function test_client_scalar_values_keep_their_type(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array(
				'pi' => 42,
				'ch' => true,
			)
		);

		$this->assertSame( 42, $sanitized['pi'] );
		$this->assertTrue( $sanitized['ch'] );
	}

	/**
	 * Reject unusable client event names.
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
			'oversized'  => array( str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH + 1 ) ),
		);
	}

	/**
	 * Keep event names at the length limit.
	 */
	public function test_client_event_name_at_the_length_limit_is_recorded(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$this->reset_pixel_batch_queue();

		$name = str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH );

		WC_Analytics_Tracking::record_client_event( $name, array() );

		$props = $this->get_queued_pixel_props();

		$this->assertSame( WC_Analytics_Tracking::PREFIX . $name, $props['_en'] ?? null );
	}

	/**
	 * Drop invalid client property names.
	 */
	public function test_unusable_client_property_names_are_dropped(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array(
				str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH + 1 ) => 'oversized',
				'Uppercase' => 'bad charset',
				'has space' => 'bad charset',
				'pi'        => 42,
				'_lg'       => 'en-GB',
			)
		);

		$this->assertSame( array( 'pi' => 42, '_lg' => 'en-GB' ), $sanitized );
	}

	/**
	 * Drop numeric client property names.
	 */
	public function test_numeric_client_property_names_are_dropped(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array(
				'0'  => 'dropped',
				'pi' => 42,
			)
		);

		$this->assertSame( array( 'pi' => 42 ), $sanitized );
	}

	/**
	 * Do not generate warnings for non-scalar session values.
	 */
	public function test_a_non_scalar_session_value_writes_no_warning(): void {
		$_COOKIE['tk_ai']                        = 'test-visitor-id-1234567890ab';
		$_COOKIE['woocommerceanalytics_session'] = wp_slash(
			(string) wp_json_encode( array( 'is_engaged' => array( array( 'nested' ) ) ) )
		);

		$warnings = array();
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure
			function ( $errno, $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			}
		);
		WC_Analytics_Tracking::record_client_event( 'product_view', array( 'pi' => 42 ) );
		restore_error_handler();

		$this->assertSame( array(), $warnings, 'A cookie value must not be able to write PHP warnings.' );
	}

	/**
	 * Keep oversized landing-page trails valid JSON.
	 */
	public function test_an_oversized_landing_page_stays_valid_json(): void {
		$trail = array_fill( 0, 200, 'Category' );

		$_COOKIE['woocommerceanalytics_session'] = wp_slash(
			(string) wp_json_encode( array( 'landing_page' => wp_json_encode( $trail ) ) )
		);

		$properties = WC_Analytics_Tracking::get_common_properties();
		$decoded    = json_decode( $properties['landing_page'], true );

		$this->assertLessThanOrEqual(
			WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH,
			mb_strlen( $properties['landing_page'] )
		);
		$this->assertIsArray( $decoded, 'A trimmed trail must still parse as JSON.' );
		$this->assertNotEmpty( $decoded );
		$this->assertSame( 'Category', $decoded[0], 'The leading entries are the ones kept.' );
	}

	/**
	 * Trim long referrers without dropping the event.
	 */
	public function test_a_long_referer_costs_its_own_tail_not_the_event(): void {
		$_COOKIE['tk_ai']        = 'test-visitor-id-1234567890ab';
		$_SERVER['HTTP_REFERER'] = 'https://example.com/?q=' . str_repeat( 'a', 5000 );
		$this->reset_pixel_batch_queue();

		$result = WC_Analytics_Tracking::record_client_event( 'product_view', array( 'pi' => 42 ) );

		$this->assertFalse( is_wp_error( $result ), 'A long request header must not cost the event.' );

		$props = $this->get_queued_pixel_props();

		$this->assertSame( '42', $props['pi'] ?? null, 'The event payload must survive intact.' );
		$this->assertStringEndsWith( '…', $props['_dr'] ?? '', 'The referer is what gets trimmed.' );

		$this->reset_pixel_batch_queue();
	}

	/**
	 * Preserve common ad-click landing URLs.
	 */
	public function test_an_ad_click_landing_url_survives_untouched(): void {
		$url = 'https://example.com/product-category/clothing/mens-shirts/?utm_source=google&utm_medium=cpc&utm_campaign=spring&gclid=Cj0KCQjw1viWBhD0ARIsAAM_oKnLQ8example1234567890abcdefghij&fbclid=IwAR2example1234567890abcdefghijklmnop';

		$this->assertGreaterThan( 200, mb_strlen( $url ), 'A fixture under the old cap would prove nothing.' );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( '_dl' => $url ) );

		$this->assertSame( $url, $sanitized['_dl'] ?? null );
	}

	/**
	 * Trim values that exceed the encoded payload budget.
	 */
	public function test_a_value_the_budget_cannot_fit_is_trimmed_not_dropped(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array( 'pn' => str_repeat( '漢', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) )
		);

		$this->assertArrayHasKey( 'pn', $sanitized, 'An over-budget value must be trimmed, not dropped.' );
		$this->assertStringEndsWith( '…', $sanitized['pn'] );
	}

	/**
	 * Keep the MU-plugin template aligned with the package API.
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

		// Nothing executes the template, so the direction of the comparison has to
		// be asserted literally: inverting it serves exactly the requests it is
		// there to refuse, and every other test stays green.
		$this->assertStringContainsString(
			"if ( 'yes' !== get_option( \\Automattic\\Woocommerce_Analytics::PROXY_TRACKING_ENABLED_OPTION ) ) {",
			$template,
			'The template must refuse unless the option says yes; the REST gate cannot reach it.'
		);
		$this->assertStringContainsString(
			"'code'    => 'proxy_tracking_disabled',",
			$template,
			'Both paths must refuse with one body shape, or a client has two to recognise.'
		);
		// Without the return, the module answers 403 and records the event anyway.
		$this->assertStringContainsString(
			"\t\t\t\t403\n\t\t\t);\n\t\t\treturn;",
			$template,
			'The refusal must stop the request, not just write a 403 body.'
		);

		$this->assertStringContainsString(
			'MAX_CLIENT_EVENTS_PER_REQUEST',
			$template,
			'The template must cap the batch with the same constant as the REST controller.'
		);
		// The autoloader resolves the highest version across active plugins, which can
		// predate this file, so every package constant it reads needs a defined() guard
		// in load_autoloader(). Collected rather than listed: a constant added to the
		// template without a guard is a 500 nothing can fall back from.
		preg_match_all( '/\\\\Automattic\\\\[A-Za-z_\\\\]*::[A-Z][A-Z0-9_]*/', $template, $reads );
		preg_match_all( '/defined\\( \'([^\']+)\' \)/', $template, $guards );

		$this->assertNotEmpty( $reads[0], 'The template is expected to read package constants.' );

		foreach ( array_unique( $reads[0] ) as $constant ) {
			$this->assertContains(
				$constant,
				$guards[1],
				"load_autoloader() must check {$constant} exists before process_proxy_request() reads it."
			);
			$this->assertTrue(
				defined( $constant ),
				"The guarded name must resolve, or load_autoloader() always returns false and the module never serves. Reviewers read the leading backslash in {$constant} as breaking defined(); it does not, on any PHP this package supports."
			);
		}
	}

	/**
	 * Keep the documented bounds in sync with their constants.
	 */
	public function test_client_bounds_have_their_documented_values(): void {
		$this->assertSame( 50, WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST );
		$this->assertSame( 50, WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT );
		$this->assertSame( 50, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS );
		$this->assertSame( 1000, WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		$this->assertSame( 100, WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH );
		$this->assertSame( 4096, WC_Analytics_Tracking::MAX_CLIENT_PAYLOAD_LENGTH );
		$this->assertSame( 8192, WC_Analytics_Tracking::MAX_PIXEL_URL_LENGTH );
	}

	/**
	 * Keep the largest permitted payload below the fixed pixel URL limit.
	 */
	public function test_a_maximal_client_payload_stays_under_eight_kilobytes(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';

		$properties = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT; $i++ ) {
			$key                = str_repeat( 'k', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH - 3 ) . sprintf( '%03d', $i );
			$properties[ $key ] = str_repeat( '漢', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		}

		$all = WC_Analytics_Tracking::get_properties(
			WC_Analytics_Tracking::PREFIX . 'bounds_probe',
			WC_Analytics_Tracking::sanitize_client_properties( $properties ),
			true
		);

		$this->assertLessThanOrEqual(
			8192,
			strlen( Pixel_Builder::build_tracks_url( $all ) ),
			'The per-axis caps must not multiply past the pixel URL ceiling.'
		);

		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * Include property names in the payload budget.
	 */
	public function test_property_names_are_charged_to_the_payload_budget(): void {
		$short = array();
		$long  = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT; $i++ ) {
			$value                = str_repeat( 'v', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
			$short[ 's' . $i ]    = $value;
			$key                  = str_repeat( 'k', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH - 3 ) . sprintf( '%03d', $i );
			$long[ $key ]         = $value;
		}

		$this->assertLessThan(
			count( WC_Analytics_Tracking::sanitize_client_properties( $short ) ),
			count( WC_Analytics_Tracking::sanitize_client_properties( $long ) ),
			'Long property names must consume budget, or the cap under-counts the URL.'
		);
	}

	/**
	 * Drop arrays that cannot retain a member within the payload budget.
	 */
	public function test_an_array_that_cannot_fit_even_one_member_is_dropped(): void {
		$properties = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$properties[ 'p' . $i ] = str_repeat( 'v', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		}
		$properties['pc'] = array( str_repeat( 'm', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );

		$this->assertArrayNotHasKey( 'pc', $sanitized, 'An emptied array must be dropped, not sent as an empty value.' );
	}
}
