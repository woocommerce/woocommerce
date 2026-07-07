<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Geolocation
 */

/**
 * Class WC_Tests_Geolocation
 */
class WC_Tests_Geolocation extends WC_Unit_Test_Case {
	public function test_get_ip_address() {
		$_SERVER['HTTP_X_REAL_IP'] = '208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_REAL_IP'] = '2620:0:ccc::2';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		unset( $_SERVER['HTTP_X_REAL_IP'] );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '2620:0:ccc::2';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220, 8.8.8.8';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '2620:0:ccc::2, 2001:4860:4860::8888';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220:1234';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220:1234, 8.8.8.8';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '[2620:0:ccc::2]';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '[2620:0:ccc::2], 2001:4860:4860::8888';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '[2620:0:ccc::2]:1234';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '[2620:0:ccc::2]:1234, 2001:4860:4860::8888';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$_SERVER['REMOTE_ADDR'] = '208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['REMOTE_ADDR'] = '208.67.220.220, 208.67.220.220, 208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['REMOTE_ADDR'] = '2620:0:ccc::2';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['REMOTE_ADDR'] = '2620:0:ccc::2, 2001:4860:4860::8888';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		unset( $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Read the value of a private static property on WC_Geolocation.
	 *
	 * @param string $property Property name.
	 * @return mixed
	 */
	private function get_private_static( $property ) {
		$ref = new ReflectionProperty( 'WC_Geolocation', $property );
		$ref->setAccessible( true );

		return $ref->getValue();
	}

	/**
	 * @testdox Every geo-IP lookup endpoint should use HTTPS so visitor IP addresses are never sent unencrypted.
	 */
	public function test_geoip_apis_all_use_https() {
		$apis = $this->get_private_static( 'geoip_apis' );

		foreach ( $apis as $service_name => $endpoint ) {
			$this->assertStringStartsWith( 'https://', $endpoint, "Geo-IP service {$service_name} must use HTTPS" );
		}

		$this->assertArrayNotHasKey( 'ip-api.com', $apis, 'The HTTP-only ip-api.com provider should no longer be used' );
	}

	/**
	 * @testdox Every IP-lookup endpoint should use HTTPS so visitor IP addresses are never sent unencrypted.
	 */
	public function test_ip_lookup_apis_all_use_https() {
		$apis = $this->get_private_static( 'ip_lookup_apis' );

		foreach ( $apis as $service_name => $endpoint ) {
			$this->assertStringStartsWith( 'https://', $endpoint, "IP-lookup service {$service_name} must use HTTPS" );
		}
	}

	/**
	 * @testdox Geolocating via the API should parse the country code and only request HTTPS endpoints.
	 */
	public function test_geolocate_via_api_uses_https_and_parses_country() {
		$ip_address    = '8.8.8.8';
		$requested_url = '';

		delete_transient( 'geoip_' . $ip_address );

		// Force the database lookup to return nothing so the API fallback runs.
		$force_empty_geolocation = function ( $data ) {
			$data['country'] = '';
			return $data;
		};
		add_filter( 'woocommerce_get_geolocation', $force_empty_geolocation, 999 );

		// Intercept the outgoing request; the body is valid for both configured providers.
		$intercept_request = function ( $pre, $args, $url ) use ( &$requested_url ) {
			$requested_url = $url;
			return array(
				'body'     => wp_json_encode( array( 'country' => 'US' ) ),
				'response' => array( 'code' => 200 ),
			);
		};
		add_filter( 'pre_http_request', $intercept_request, 10, 3 );

		$geolocation = WC_Geolocation::geolocate_ip( $ip_address, false, true );

		remove_filter( 'pre_http_request', $intercept_request, 10 );
		remove_filter( 'woocommerce_get_geolocation', $force_empty_geolocation, 999 );
		delete_transient( 'geoip_' . $ip_address );

		$this->assertEquals( 'US', $geolocation['country'], 'The country code from the API response should be returned' );
		$this->assertStringStartsWith( 'https://', $requested_url, 'The geolocation request must be made over HTTPS' );
	}
}
