<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Geolocation
 */

/**
 * Class WC_Tests_Integrations
 */
class WC_Tests_Geolite_Integration extends WC_Unit_Test_Case {

	/**
	 * Test get country ISO.
	 *
	 * @requires PHP 5.4
	 */
	public function test_get_country_iso() {
		require_once dirname( WC_PLUGIN_FILE ) . '/includes/class-wc-geolite-integration.php';

		// Download GeoLite2 database.
		WC_Geolocation::update_database();

		// OpenDNS IP address.
		$ipv4 = '208.67.220.220';
		$ipv6 = '2620:0:ccc::2';

		// Init GeoLite.
		$geolite = new WC_Geolite_Integration( WC_Geolocation::get_local_database_path() );

		// Check for IPv4.
		$this->assertEquals( 'US', $geolite->get_country_iso( $ipv4 ) );

		// Check for IPv6.
		$this->assertEquals( 'US', $geolite->get_country_iso( $ipv6 ) );

		// Check for non-valid IP.
		$this->assertEquals( '', $geolite->get_country_iso( 'foobar' ) );
	}
}
