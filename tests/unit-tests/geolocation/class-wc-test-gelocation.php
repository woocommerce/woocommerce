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
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		$_SERVER['REMOTE_ADDR'] = '208.67.220.220';
		$this->assertEquals( '208.67.220.220', WC_Geolocation::get_ip_address() );
		$_SERVER['REMOTE_ADDR'] = '2620:0:ccc::2';
		$this->assertEquals( '2620:0:ccc::2', WC_Geolocation::get_ip_address() );
		unset( $_SERVER['REMOTE_ADDR'] );
	}
}
