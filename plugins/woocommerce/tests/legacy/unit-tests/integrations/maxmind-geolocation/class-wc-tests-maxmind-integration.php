<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Integrations
 */

/**
 * Class WC_Tests_MaxMind_Integration
 */
class WC_Tests_MaxMind_Integration extends WC_Unit_Test_Case {

	/**
	 * The mock database service that our integration class will utilize.
	 *
	 * @var WC_Integration_MaxMind_Database_Service|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $database_service;

	/**
	 * Run setup code for unit tests.
	 */
	public function setUp(): void {
		parent::setUp();

		// Override the filesystem method that we're using.
		add_filter( 'filesystem_method', array( $this, 'override_filesystem_method' ) );

		// Have a mock service be used by all integrations.
		$this->database_service = $this->getMockBuilder( 'WC_Integration_maxMind_Database_Service' )
			->disableOriginalConstructor()
			->getMock();
		add_filter( 'woocommerce_maxmind_geolocation_database_service', array( $this, 'override_integration_service' ) );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		unset( $GLOBALS['wp_filesystem'] );

		remove_filter( 'filesystem_method', array( $this, 'override_filesystem_method' ) );
		remove_filter( 'woocommerce_maxmind_geolocation_database_service', array( $this, 'override_integration_service' ) );

		parent::tearDown();
	}

	/**
	 * Make sure that the database is not updated if no target database path is given.
	 */
	public function test_update_database_does_nothing_without_database_path() {
		$this->database_service->expects( $this->once() )
			->method( 'get_database_path' )
			->willReturn( '' );

		( new WC_Integration_MaxMind_Geolocation() )->update_database();
	}

	/**
	 * Makes sure that the database can be updated to a given database.
	 */
	public function test_update_database_to_parameter_file() {
		$this->database_service->expects( $this->once() )
			->method( 'get_database_path' )
			->willReturn( '/testing' );

		( new WC_Integration_MaxMind_Geolocation() )->update_database( '/tmp/noop.mmdb' );
	}

	/**
	 * Makes sure that the integration uses the license key correctly.
	 */
	public function test_update_database_uses_license_key() {
		$this->database_service->expects( $this->once() )
			->method( 'get_database_path' )
			->willReturn( '/testing' );
		$this->database_service->expects( $this->once() )
			->method( 'download_database' )
			->with( 'test_license' )
			->willReturn( '/tmp/' . WC_Integration_MaxMind_Database_Service::DATABASE . '.' . WC_Integration_MaxMind_Database_Service::DATABASE_EXTENSION );

		$integration = new WC_Integration_MaxMind_Geolocation();
		$integration->update_option( 'license_key', 'test_license' );

		$integration->update_database();
	}

	/**
	 * Make sure that the geolocate_ip method does not squash existing country codes.
	 */
	public function test_geolocate_ip_returns_existing_country_code() {
		$data = ( new WC_Integration_MaxMind_Geolocation() )->get_geolocation( array( 'country' => 'US' ), '192.168.1.1' );

		$this->assertEquals( 'US', $data['country'] );
	}

	/**
	 * Make sure that the geolocate_ip method does nothing if IP is not set.
	 */
	public function test_geolocate_ip_returns_empty_without_ip_address() {
		$data = ( new WC_Integration_MaxMind_Geolocation() )->get_geolocation( array(), '' );

		$this->assertEmpty( $data );
	}

	/**
	 * Make sure that the geolocate_ip method uses the appropriate service methods..
	 */
	public function test_geolocate_ip_uses_service() {
		$this->database_service->expects( $this->once() )
			->method( 'get_iso_country_code_for_ip' )
			->with( '192.168.1.1' )
			->willReturn( 'US' );

		$data = ( new WC_Integration_MaxMind_Geolocation() )->get_geolocation( array(), '192.168.1.1' );

		$this->assertEquals( 'US', $data['country'] );
	}

	/**
	 * Overrides the filesystem method.
	 *
	 * @return string
	 */
	public function override_filesystem_method() {
		return 'Base';
	}

	/**
	 * Overrides the database service used by the integration.
	 *
	 * @return mixed
	 */
	public function override_integration_service() {
		return $this->database_service;
	}
}
