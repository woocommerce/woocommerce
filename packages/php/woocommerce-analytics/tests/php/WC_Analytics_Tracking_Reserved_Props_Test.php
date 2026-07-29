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
	}

	/**
	 * Clear the memoized reserved-name list after each test.
	 */
	public function tear_down(): void {
		$this->reset_reserved_property_names();
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
}
