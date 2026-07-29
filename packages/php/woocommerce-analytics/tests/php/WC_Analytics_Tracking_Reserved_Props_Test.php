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
	 * Clear the memoized reserved-name list before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->reset_reserved_property_names();
		$this->reset_pixel_batch_queue();
	}

	/**
	 * Clear the memoized reserved-name list after each test.
	 */
	public function tear_down(): void {
		$this->reset_reserved_property_names();
		$this->reset_pixel_batch_queue();
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
	 */
	public function test_record_client_event_drops_client_supplied_server_properties(): void {
		$_COOKIE['tk_ai'] = 'test-visitor-id-1234567890ab';
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

		$this->assertNotSame( '8.8.8.8', $props['_via_ip'] ?? null, '_via_ip must come from the server.' );
		$this->assertNotSame( 'someone-elses-store', $props['store_id'] ?? null, 'store_id must come from the server.' );
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
}
