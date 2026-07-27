<?php
/**
 * Tests for visitor IP resolution.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WorDBless\BaseTestCase;

/**
 * Tests for the visitor IP that reaches `_via_ip` and the proxy-mode visitor id.
 *
 * Request headers are forgeable, so resolution must default to REMOTE_ADDR and only
 * consult a proxy header the site has explicitly declared trustworthy.
 */
class Ip_Address_Test extends BaseTestCase {

	/**
	 * Prior state of every $_SERVER key this class touches, recording absence as well
	 * as value so tear_down() can restore rather than blanket-unset.
	 *
	 * @var array<string, array{0: bool, 1: mixed}>
	 */
	private $server_snapshot = array();

	/**
	 * Filters added by a test, removed in tear_down() so they cannot leak between cases.
	 *
	 * @var array<int, array{0: string, 1: callable}>
	 */
	private $added_filters = array();

	/**
	 * Set up.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->snapshot_server();
		$this->reset_tracking_static_state();
		unset( $_COOKIE['tk_ai'] );
		delete_site_option( 'trusted_ip_header' );
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		foreach ( $this->added_filters as $entry ) {
			remove_filter( $entry[0], $entry[1] );
		}
		$this->added_filters = array();

		$this->restore_server();
		$this->reset_tracking_static_state();
		unset( $_COOKIE['tk_ai'] );
		delete_site_option( 'trusted_ip_header' );
		parent::tear_down();
	}

	/**
	 * The $_SERVER keys this class mutates.
	 *
	 * @return string[]
	 */
	private function mutated_server_keys(): array {
		return array(
			'REMOTE_ADDR',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'HTTP_USER_AGENT',
			'HTTP_X_REAL_IP',
		);
	}

	/**
	 * Record whether each mutated key exists and what it holds.
	 */
	private function snapshot_server(): void {
		$this->server_snapshot = array();
		foreach ( $this->mutated_server_keys() as $key ) {
			$this->server_snapshot[ $key ] = array_key_exists( $key, $_SERVER )
				? array( true, $_SERVER[ $key ] )
				: array( false, null );
		}
	}

	/**
	 * Put every mutated key back exactly as it was, removing only keys that were absent.
	 */
	private function restore_server(): void {
		foreach ( $this->server_snapshot as $key => $state ) {
			if ( $state[0] ) {
				$_SERVER[ $key ] = $state[1];
			} else {
				unset( $_SERVER[ $key ] );
			}
		}
	}

	/**
	 * Clear the per-request static caches so each case resolves from scratch.
	 */
	private function reset_tracking_static_state(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		foreach ( array( 'cached_ip', 'cached_visitor_id' ) as $name ) {
			$property = $reflection->getProperty( $name );
			$property->setAccessible( true );
			$property->setValue( null, null );
		}
	}

	/**
	 * Add a filter and register it for automatic removal in tear_down().
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 */
	private function add_temporary_filter( string $hook, callable $callback ): void {
		add_filter( $hook, $callback );
		$this->added_filters[] = array( $hook, $callback );
	}

	/**
	 * Resolve the visitor IP through its public caller.
	 *
	 * @return string
	 */
	private function resolved_ip(): string {
		$this->reset_tracking_static_state();
		$details = WC_Analytics_Tracking::get_server_details();
		return $details['_via_ip'];
	}

	/**
	 * A public REMOTE_ADDR is reported as-is.
	 */
	public function test_public_remote_addr_is_used(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->assertSame( '203.0.113.10', $this->resolved_ip() );
	}

	/**
	 * The core of this change: proxy headers are forgeable, so without a site-declared
	 * trusted header none of them may displace REMOTE_ADDR.
	 */
	public function test_proxy_headers_are_ignored_without_a_trusted_header(): void {
		$_SERVER['REMOTE_ADDR']           = '203.0.113.10';
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.99';
		$_SERVER['HTTP_X_FORWARDED_FOR']  = '198.51.100.98';
		$_SERVER['HTTP_CLIENT_IP']        = '198.51.100.97';

		$this->assertSame(
			'203.0.113.10',
			$this->resolved_ip(),
			'A forged proxy header must not displace REMOTE_ADDR.'
		);
	}

	/**
	 * A site that has declared a trusted header still gets the real visitor address,
	 * including the segment selection that says which entry of the list to believe.
	 */
	public function test_configured_trusted_header_is_honoured(): void {
		update_site_option(
			'trusted_ip_header',
			(object) array(
				'trusted_header' => 'HTTP_X_FORWARDED_FOR',
				'segments'       => 2,
				'reverse'        => false,
			)
		);

		$_SERVER['REMOTE_ADDR']          = '203.0.113.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.5, 203.0.113.7';

		$this->assertSame( '198.51.100.5', $this->resolved_ip() );
	}

	/**
	 * The escape hatch for proxy topologies this package cannot detect.
	 */
	public function test_filter_overrides_the_resolved_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->add_temporary_filter(
			'woocommerce_analytics_visitor_ip',
			function () {
				return '198.51.100.42';
			}
		);

		$this->assertSame( '198.51.100.42', $this->resolved_ip() );
	}

	/**
	 * Ordering guarantee: the public-address gate runs after the filter, so no callback
	 * can reintroduce an address geoip would discard.
	 */
	public function test_filter_cannot_reintroduce_a_non_public_address(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->add_temporary_filter(
			'woocommerce_analytics_visitor_ip',
			function () {
				return '10.0.0.1';
			}
		);

		$this->assertSame(
			'',
			$this->resolved_ip(),
			'ip_is_public() must gate the filtered value, not just the resolved one.'
		);
	}

	/**
	 * Non-public addresses resolve to an empty string.
	 *
	 * @dataProvider non_public_address_provider
	 *
	 * @param string $ip   Address to place in REMOTE_ADDR.
	 * @param string $note Why this case matters.
	 */
	public function test_non_public_addresses_resolve_to_empty_string( string $ip, string $note ): void {
		$_SERVER['REMOTE_ADDR'] = $ip;

		$this->assertSame( '', $this->resolved_ip(), $note );
	}

	/**
	 * The last three rows are why the dependency is constrained to ^0.5: PHP's
	 * FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE accepts all three, and
	 * jetpack-ip 0.4.x's ip_is_private() only knows five hard-coded IPv4 ranges that
	 * do not include them. Only ip_is_public() rejects them.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function non_public_address_provider(): array {
		return array(
			'private class A' => array( '10.0.0.1', 'RFC 1918 class A' ),
			'private class B' => array( '172.16.0.1', 'RFC 1918 class B, missed by the old preg_match' ),
			'loopback'        => array( '127.0.0.1', 'Loopback, missed by the old preg_match' ),
			'link-local'      => array( '169.254.169.254', 'Cloud metadata address' ),
			'IPv6 ULA'        => array( 'fd00::1', 'IPv6 unique local address' ),
			'CGNAT'           => array( '100.64.0.1', 'RFC 6598 — only ip_is_public() rejects this' ),
			'multicast'       => array( '224.0.0.1', 'RFC 5771 — only ip_is_public() rejects this' ),
			'benchmarking'    => array( '198.18.0.1', 'RFC 2544 — only ip_is_public() rejects this' ),
		);
	}

	/**
	 * In proxy mode the IP feeds the visitor id hash. With no tk_ai cookie and no public
	 * address there is no stable id to attribute an event to, so resolution must return
	 * null rather than fabricate a visitor.
	 */
	public function test_proxy_mode_has_no_visitor_id_without_a_public_ip(): void {
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		unset( $_COOKIE['tk_ai'] );

		$this->add_temporary_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );

		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$method     = $reflection->getMethod( 'get_visitor_id' );
		$method->setAccessible( true );

		$this->assertNull(
			$method->invoke( null ),
			'A non-public IP must not produce an IP-derived visitor id in proxy mode.'
		);
	}

	/**
	 * A private REMOTE_ADDR proves the request came through a proxy on the local network,
	 * so the address that proxy recorded is usable.
	 */
	public function test_internal_proxy_supplies_the_visitor_address(): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.5';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10';

		$this->assertSame( '203.0.113.10', $this->resolved_ip() );
	}

	/**
	 * The client-supplied end of the list must lose. A client sending its own
	 * X-Forwarded-For produces "<forged>, <real>" once the proxy appends what it saw.
	 */
	public function test_forged_forwarded_entry_loses_to_the_proxy_written_one(): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.5';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.99, 203.0.113.10';

		$this->assertSame(
			'203.0.113.10',
			$this->resolved_ip(),
			'Only the last X-Forwarded-For entry is written by the proxy.'
		);
	}

	/**
	 * With a public REMOTE_ADDR the client is talking to us directly, so any forwarded
	 * header it sends is its own invention and must be ignored outright.
	 */
	public function test_forwarded_header_is_ignored_when_remote_addr_is_public(): void {
		$_SERVER['REMOTE_ADDR']          = '203.0.113.10';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.99';

		$this->assertSame( '203.0.113.10', $this->resolved_ip() );
	}

	/**
	 * Cf-Connecting-IP and X-Real-IP are passed through verbatim by an internal proxy
	 * unless it is configured to overwrite them, so they stay client-controlled and
	 * unusable even on a private REMOTE_ADDR.
	 */
	public function test_other_forwarded_headers_are_never_trusted(): void {
		$_SERVER['REMOTE_ADDR']           = '10.0.0.5';
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.99';
		$_SERVER['HTTP_X_REAL_IP']        = '198.51.100.98';

		$this->assertSame( '', $this->resolved_ip() );
	}

	/**
	 * Chained internal proxies leave a private address in the last entry. Scanning further
	 * left would walk into client-supplied values, so no IP is the correct answer.
	 *
	 * Asserted directly against get_proxy_written_ip_address() rather than only through
	 * resolved_ip(): the final ip_is_public() gate zeroes both "helper returned ''" and
	 * "helper returned 10.0.0.6" the same way, so that assertion alone cannot prove the
	 * helper read the last entry rather than, say, discarding a private one. A reflection
	 * call on the helper pins that it returns the last entry verbatim (10.0.0.6, still
	 * private) rather than scanning left for '203.0.113.10' — the mutation this test exists
	 * to catch.
	 */
	public function test_chained_internal_proxies_yield_no_address(): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.5';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10, 10.0.0.6';

		$this->assertSame( '', $this->resolved_ip() );

		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$method     = $reflection->getMethod( 'get_proxy_written_ip_address' );
		$method->setAccessible( true );

		$this->assertSame(
			'10.0.0.6',
			$method->invoke( null ),
			'The helper must read the last entry verbatim, not scan left for a public-looking one.'
		);
	}

	/**
	 * A trailing comma in `X-Forwarded-For` leaves the last entry empty. That empty entry
	 * must resolve to no address rather than being skipped in favor of the earlier,
	 * client-controlled entry in front of it — the same left-of-the-proxy value a real
	 * attacker would put there to be picked up if the code fell back to it.
	 */
	public function test_trailing_comma_yields_no_address(): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.5';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10,';

		$this->assertSame(
			'',
			$this->resolved_ip(),
			'An empty last entry must resolve to no address, not silently fall back to an earlier one.'
		);
	}

	/**
	 * Regression: get_ip() must never be the value tested for "is REMOTE_ADDR private". With
	 * trusted_ip_header configured, get_ip() selects a segment out of a client-supplied
	 * X-Forwarded-For list, so it can be driven non-public by an attacker connecting directly
	 * (a public REMOTE_ADDR) who simply prepends a private-looking address of their choosing.
	 * If the proxy-recovery branch keyed off get_ip()'s result instead of REMOTE_ADDR itself,
	 * this would then read the *last* X-Forwarded-For entry — also attacker-controlled here,
	 * since there is no real proxy in front of this request — and report it as the visitor IP.
	 */
	public function test_forwarded_header_is_ignored_when_get_ip_result_is_private_but_remote_addr_is_public(): void {
		update_site_option(
			'trusted_ip_header',
			(object) array(
				'trusted_header' => 'HTTP_X_FORWARDED_FOR',
				'segments'       => 2,
				'reverse'        => false,
			)
		);

		$_SERVER['REMOTE_ADDR']          = '198.51.100.66';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1, 8.8.8.8';

		$this->assertNotSame(
			'8.8.8.8',
			$this->resolved_ip(),
			'REMOTE_ADDR is public and directly connected, so no forwarded value may be trusted.'
		);
	}

	/**
	 * A private REMOTE_ADDR with nothing forwarded stays unresolved.
	 */
	public function test_private_remote_addr_without_forwarded_header_yields_no_address(): void {
		$_SERVER['REMOTE_ADDR'] = '10.0.0.5';

		$this->assertSame( '', $this->resolved_ip() );
	}

	/**
	 * Proxies write ports and bracketed IPv6; clean_ip() normalises both.
	 *
	 * @dataProvider forwarded_address_format_provider
	 *
	 * @param string $forwarded Raw last X-Forwarded-For entry.
	 * @param string $expected  Normalised address.
	 */
	public function test_forwarded_address_formats_are_normalised( string $forwarded, string $expected ): void {
		$_SERVER['REMOTE_ADDR']          = '10.0.0.5';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = $forwarded;

		$this->assertSame( $expected, $this->resolved_ip() );
	}

	/**
	 * Address forms a proxy can legitimately write.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function forwarded_address_format_provider(): array {
		return array(
			'plain IPv4'          => array( '203.0.113.10', '203.0.113.10' ),
			'IPv4 with port'      => array( '203.0.113.10:51234', '203.0.113.10' ),
			'spaced list'         => array( '198.51.100.99 , 203.0.113.10', '203.0.113.10' ),
			'bracketed IPv6'      => array( '[2001:db8::1]:443', '2001:db8::1' ),
			'IPv4-mapped IPv6'    => array( '::ffff:203.0.113.10', '203.0.113.10' ),
		);
	}
}
