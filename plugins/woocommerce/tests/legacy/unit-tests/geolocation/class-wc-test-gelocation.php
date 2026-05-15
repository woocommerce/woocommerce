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
		// Apple iCloud Private Relay (and similar proxies) can populate
		// X-Real-IP with a comma-separated list, sometimes with the same
		// address duplicated. Only the first valid IP should be returned.
		$_SERVER['HTTP_X_REAL_IP'] = '104.28.28.0, 104.28.28.0, 104.28.28.0';
		$this->assertEquals( '104.28.28.0', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_REAL_IP'] = '208.67.220.220, 8.8.8.8';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_REAL_IP'] = '208.67.220.220:1234';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_REAL_IP'] = '[2620:0:ccc::2]:1234';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_REAL_IP'] = 'not-an-ip';
		$this->assertEquals( '', WC_Geolocation::get_ip_address() );
		unset( $_SERVER['HTTP_X_REAL_IP'] );

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '2620:0:ccc::2';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '208.67.220.220, 8.8.8.8';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '2620:0:ccc::2, 2001:4860:4860::8888';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		// Regression: Apple iCloud Private Relay duplicated IP list (woo#63978).
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '104.28.28.0, 104.28.28.0, 104.28.28.0';
		$this->assertEquals( '104.28.28.0', WC_Geolocation::get_ip_address() );

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
}
