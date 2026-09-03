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
	 * The endpoint is unauthenticated and every property reaches the pixel URL,
	 * which is rejected outright once it grows too long. Values are truncated
	 * with an ellipsis so a capped value stays distinguishable from one that
	 * genuinely ended at the limit.
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
	 * A client cannot widen its own footprint by sending hundreds of properties.
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
	 * get_properties() flattens array values with implode(), which emits an
	 * "Array to string conversion" warning for a nested array. Letting an
	 * unauthenticated caller write warnings into the error log is the problem
	 * worth closing; the value itself is meaningless either way.
	 */
	public function test_nested_client_arrays_do_not_reach_the_flattening_step(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array( 'foo' => array( array( 1, 2 ), 'ok' ) )
		);

		$this->assertSame( array( '', 'ok' ), $sanitized['foo'] );
	}

	/**
	 * The per-value cap runs on each member, never on the string
	 * `get_properties()` joins them into: 20,000 ten-character members produced a
	 * 300KB pixel URL, which nothing downstream rejects.
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
	 * Slicing must not turn an indexed array into an associative one:
	 * `get_properties()` picks `implode()` over `wp_json_encode()` on that test.
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
	 * The per-axis caps multiply: at their limits one event built a 512KB pixel
	 * URL, so the product needs its own bound. Properties are dropped from the
	 * tail once the budget is spent.
	 */
	public function test_client_payload_total_is_capped( string $character = 'a' ): void {
		$properties = array();
		for ( $i = 0; $i < WC_Analytics_Tracking::MAX_CLIENT_PROPERTIES_PER_EVENT; $i++ ) {
			$properties[ 'p' . $i ] = str_repeat( $character, WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );
		}

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( $properties );

		$this->assertNotEmpty( $sanitized, 'The budget drops the tail, it does not empty the event.' );

		// The pixel URL is what the budget exists to bound, so assert on it rather
		// than on the constant.
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
		$props            = WC_Analytics_Tracking::get_properties( 'woocommerceanalytics_product_view', $sanitized, true );

		$this->assertLessThanOrEqual(
			WC_Analytics_Tracking::MAX_PIXEL_URL_LENGTH,
			strlen( Pixel_Builder::build_tracks_url( $props ) )
		);

		unset( $_COOKIE['tk_ai'] );
	}

	/**
	 * An ASCII-only fixture hid this: the budget counted characters while the URL
	 * carries percent-encoded bytes, so `%` costs 3x and CJK and emoji 9x or more
	 * of what the budget charged, and the same payload built a 12KB URL.
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
	 * An associative array serializes to JSON, which carries its keys, while an
	 * indexed one is joined and drops them. Measuring both as a joined list
	 * charged nothing for the keys, and 50 such properties built a 152KB URL.
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
	 * One oversized value used to charge its key against the budget and then be
	 * dropped anyway, so short properties after it were refused for space that
	 * nothing occupied.
	 */
	public function test_a_dropped_property_does_not_spend_the_budget(): void {
		// Long names on purpose: the leak is the key's own cost, so short keys hide
		// it however many properties are dropped.
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
	 * The cut is made in characters, never in bytes, so a multibyte value comes
	 * back shorter but never cut mid-sequence.
	 *
	 * No equality against the value cap here: a multibyte value at the cap costs
	 * several times its length once percent-encoded, so the payload budget trims
	 * it further, and asserting the cap would be asserting which bound won.
	 * `test_client_property_values_are_capped()` pins the cap on ASCII, where
	 * nothing else is in play.
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
	 * Multibyte characters whose byte length differs from their character length.
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
	 * The bound is a limit, not an off-by-one rejection of the longest legal value.
	 */
	public function test_a_value_at_the_length_limit_is_untouched(): void {
		$exact = str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pn' => $exact ) );

		$this->assertSame( $exact, $sanitized['pn'] );
	}

	/**
	 * An array that hits the member cap always exceeded the budget, so the whole
	 * property used to vanish. Losing a few categories is easier to notice.
	 */
	public function test_oversized_arrays_lose_members_not_the_property(): void {
		$members = array_fill( 0, WC_Analytics_Tracking::MAX_CLIENT_ARRAY_MEMBERS, str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( 'pc' => $members ) );

		$this->assertArrayHasKey( 'pc', $sanitized, 'The property must survive with fewer members.' );
		$this->assertLessThan( count( $members ), count( $sanitized['pc'] ) );
	}

	/**
	 * The last bound, and the only one that sees the final URL — so the only one
	 * covering properties a filter callback added after the client caps ran.
	 * Driven through the trusted path, where no client cap applies.
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
	 * A realistic event must pass through untouched, or the budget is capping
	 * first-party analytics rather than an attacker's payload.
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
	 * Every other bounds test calls `sanitize_client_properties()` directly, so
	 * removing its call site in `record_event()` left the suite green while the
	 * bounds silently stopped applying. This drives one through the real entry
	 * point instead.
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
	 * Non-string scalars are analytics payload, not text: capping them would
	 * change their type on the way to the pixel.
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
			'oversized'  => array( str_repeat( 'a', WC_Analytics_Tracking::MAX_CLIENT_NAME_LENGTH + 1 ) ),
		);
	}

	/**
	 * A name at the limit is still recorded: the bound is a limit, not an
	 * off-by-one rejection of the longest legal name.
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
	 * Names are the one axis left unbounded once values and counts are capped.
	 * The charset check is `Pixel_Builder`'s own, so this drops exactly what it
	 * would reject — but rejecting there loses the whole event, not one property.
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
	 * A JSON object key that is all digits becomes an integer array key in PHP,
	 * which `Pixel_Builder` would reject for the whole event.
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
	 * The session cookie is client-writable and decoded with `json_decode`, so
	 * every value it carries arrives with a type the client chose. `is_engaged`
	 * was the one field read straight out of it: a nested array reached
	 * `implode()` in `get_properties()` and wrote an "Array to string conversion"
	 * warning to the log from an unauthenticated request.
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
	 * `landing_page` carries a JSON breadcrumb trail. Cutting it as a plain string
	 * lands mid-token and hands the pipeline something that no longer parses, so
	 * whole trailing entries go instead.
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
	 * The referer reaches the pixel twice, as `_dr` and `_via_ref`, and nothing
	 * bounds an HTTP header. Uncapped, one long one pushed the finished URL past
	 * MAX_PIXEL_URL_LENGTH and cost the whole event, silently: the client fires
	 * with `sendBeacon`, which discards the response.
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
	 * The fixture is an ordinary paid-traffic landing URL. At the old 200-character
	 * cap it was truncated, which destroys exactly the campaign attribution the
	 * event exists to record.
	 */
	public function test_an_ad_click_landing_url_survives_untouched(): void {
		$url = 'https://example.com/product-category/clothing/mens-shirts/?utm_source=google&utm_medium=cpc&utm_campaign=spring&gclid=Cj0KCQjw1viWBhD0ARIsAAM_oKnLQ8example1234567890abcdefghij&fbclid=IwAR2example1234567890abcdefghijklmnop';

		$this->assertGreaterThan( 200, mb_strlen( $url ), 'A fixture under the old cap would prove nothing.' );

		$sanitized = WC_Analytics_Tracking::sanitize_client_properties( array( '_dl' => $url ) );

		$this->assertSame( $url, $sanitized['_dl'] ?? null );
	}

	/**
	 * A value at the character cap can outweigh the whole byte budget once
	 * percent-encoded. Trimming it keeps the property; dropping it loses a
	 * product name to an encoding difference.
	 */
	public function test_a_value_the_budget_cannot_fit_is_trimmed_not_dropped(): void {
		$sanitized = WC_Analytics_Tracking::sanitize_client_properties(
			array( 'pn' => str_repeat( '漢', WC_Analytics_Tracking::MAX_CLIENT_PROPERTY_LENGTH ) )
		);

		$this->assertArrayHasKey( 'pn', $sanitized, 'An over-budget value must be trimmed, not dropped.' );
		$this->assertStringEndsWith( '…', $sanitized['pn'] );
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

		$this->assertStringContainsString(
			'MAX_CLIENT_EVENTS_PER_REQUEST',
			$template,
			'The template must cap the batch with the same constant as the REST controller.'
		);
		$this->assertStringContainsString(
			'defined( \'\Automattic\Woocommerce_Analytics\WC_Analytics_Tracking::MAX_CLIENT_EVENTS_PER_REQUEST\' )',
			$template,
			'The template must check that constant exists before reading it, since the autoloader can resolve an older package.'
		);
	}

	/**
	 * The bounds are the contract this endpoint is documented with, and every
	 * other assertion about them compares against the constant itself, so a
	 * changed number would go unnoticed. These literals are the tripwire.
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
	 * The worst input the per-axis caps allow must still fit the pixel URL. The
	 * literal 8192 is deliberate: asserting against MAX_PIXEL_URL_LENGTH makes
	 * the assertion move with the constant and pass whatever it is set to.
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
	 * Long property names cost budget too. Without charging the key, the budget
	 * under-counts by up to MAX_CLIENT_PROPERTIES_PER_EVENT x MAX_CLIENT_NAME_LENGTH.
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
	 * An array trimmed until nothing fits is dropped rather than sent as an
	 * empty value, so the pixel does not carry a meaningless parameter.
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
