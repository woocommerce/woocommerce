<?php
/**
 * WC_Geolocation tests.
 *
 * @package WooCommerce\Tests\Geolocation.
 */

/**
 * Class WC_Geolocation_Test.
 */
class WC_Geolocation_Test extends \WC_Unit_Test_Case {

	/**
	 * Returns the value of the private static `$ip_lookup_apis` property.
	 *
	 * @return array
	 */
	private function get_ip_lookup_apis(): array {
		$reflection = new ReflectionClass( WC_Geolocation::class );
		$property   = $reflection->getProperty( 'ip_lookup_apis' );
		$property->setAccessible( true );

		return (array) $property->getValue();
	}

	/**
	 * @testdox Should expose the ipify IP lookup API entry.
	 */
	public function test_ipify_entry_is_present(): void {
		$apis = $this->get_ip_lookup_apis();

		$this->assertArrayHasKey( 'ipify', $apis, 'ipify entry should remain available in the IP lookup APIs list.' );
	}

	/**
	 * @testdox Should not use plain HTTP for the ipify IP lookup endpoint.
	 */
	public function test_ipify_endpoint_does_not_use_plain_http(): void {
		$apis = $this->get_ip_lookup_apis();

		$this->assertArrayHasKey( 'ipify', $apis );
		$this->assertStringStartsNotWith(
			'http://',
			$apis['ipify'],
			'The ipify endpoint should not use plain HTTP because that variant does not support IPv6.'
		);
	}

	/**
	 * @testdox Should use an IPv6-capable ipify endpoint over HTTPS.
	 */
	public function test_ipify_endpoint_supports_ipv6(): void {
		$apis = $this->get_ip_lookup_apis();

		$this->assertArrayHasKey( 'ipify', $apis );
		$this->assertStringStartsWith(
			'https://',
			$apis['ipify'],
			'The ipify endpoint should be reached over HTTPS.'
		);
		$this->assertStringContainsString(
			'api64.ipify.org',
			$apis['ipify'],
			'The ipify endpoint should use the api64 subdomain so it returns both IPv4 and IPv6 addresses, matching the other lookup providers.'
		);
	}
}
