<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Integrations
 */

/**
 * Class WC_Tests_MaxMind_Database
 */
class WC_Tests_MaxMind_Database extends WC_Unit_Test_Case {

	/**
	 * Run setup code for unit tests.
	 */
	public function setUp() {
		parent::setUp();

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );
	}

	/**
	 * Tests that the database path filters work as intended.
	 *
	 * @expectedDeprecated woocommerce_geolocation_local_database_path
	 */
	public function test_database_path_filters() {
		$database_service = new WC_Integration_MaxMind_Database_Service( '' );

		$path = $database_service->get_database_path();
		$this->assertEquals( WP_CONTENT_DIR . '/uploads/woocommerce_uploads/' . WC_Integration_MaxMind_Database_Service::DATABASE . WC_Integration_MaxMind_Database_Service::DATABASE_EXTENSION, $path );

		add_filter( 'woocommerce_geolocation_local_database_path', array( $this, 'filter_database_path_deprecated' ), 1, 2 );
		$path = $database_service->get_database_path();
		remove_filter( 'woocommerce_geolocation_local_database_path', array( $this, 'filter_database_path_deprecated' ), 1 );

		$this->assertEquals( '/deprecated_filter', $path );

		add_filter( 'woocommerce_geolocation_local_database_path', array( $this, 'filter_database_path' ) );
		$path = $database_service->get_database_path();
		remove_filter( 'woocommerce_geolocation_local_database_path', array( $this, 'filter_database_path' ) );

		$this->assertEquals( '/filter', $path );

		// Now perform any tests with a database file prefix.
		$database_service = new WC_Integration_MaxMind_Database_Service( 'testing' );

		$path = $database_service->get_database_path();
		$this->assertEquals( WP_CONTENT_DIR . '/uploads/woocommerce_uploads/testing-' . WC_Integration_MaxMind_Database_Service::DATABASE . WC_Integration_MaxMind_Database_Service::DATABASE_EXTENSION, $path );
	}

	/**
	 * Tests that the database download works as expected.
	 */
	public function test_download_database_works() {
		$database_service  = new WC_Integration_MaxMind_Database_Service( '' );
		$expected_database = sys_get_temp_dir() . '/GeoLite2-Country_20200100/GeoLite2-Country.mmdb';

		self::disable_code_hacker();
		$result = $database_service->download_database( 'testing_license' );
		self::reenable_code_hacker();
		if ( is_wp_error( $result ) ) {
			$this->fail( $result->get_error_message() );
		}

		$this->assertEquals( $expected_database, $result );

		// Remove the downloaded file and folder.
		unlink( $expected_database );
		rmdir( dirname( $expected_database ) );
	}

	/**
	 * Tests the that database download wraps the download and extraction errors.
	 */
	public function test_download_database_wraps_errors() {
		$database_service = new WC_Integration_MaxMind_Database_Service( '' );

		$result = $database_service->download_database( 'invalid_license' );

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_license_key', $result->get_error_code() );

		$result = $database_service->download_database( 'generic_error' );

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_download', $result->get_error_code() );

		$result = $database_service->download_database( 'archive_error' );

		$this->assertWPError( $result );
		$this->assertEquals( 'woocommerce_maxmind_geolocation_database_archive', $result->get_error_code() );
	}

	/**
	 * Hook for the deprecated database path filter.
	 *
	 * @param string $database_path The path to the database file.
	 * @param string $deprecated Deprecated since 3.4.0.
	 * @return string
	 */
	public function filter_database_path_deprecated( $database_path, $deprecated ) {
		return '/deprecated_filter';
	}

	/**
	 * Hook for the database path filter.
	 *
	 * @param string $database_path The path to the database file.
	 * @return string
	 */
	public function filter_database_path( $database_path ) {
		return '/filter';
	}

	/**
	 * Helper method to define mocked HTTP responses using WP_HTTP_TestCase.
	 * Thanks to WP_HTTP_TestCase, it is not necessary to perform a regular request
	 * to an external server which would significantly slow down the tests.
	 *
	 * This function is called by WP_HTTP_TestCase::http_request_listner().
	 *
	 * @param array  $request Request arguments.
	 * @param string $url URL of the request.
	 *
	 * @return array|WP_Error|false mocked response, error, or false to let WP perform a regular request.
	 */
	protected function mock_http_responses( $request, $url ) {
		$mocked_response = false;

		if ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=testing_license&suffix=tar.gz' === $url ) {
			// We need to copy the file to where the request is supposed to have streamed it.
			self::file_copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/GeoLite2-Country.tar.gz', $request['filename'] );

			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=invalid_license&suffix=tar.gz' === $url ) {
			return new WP_Error( 'http_404', 'Unauthorized', array( 'code' => 401 ) );
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=generic_error&suffix=tar.gz' === $url ) {
			return new WP_Error( 'http_404', 'Unauthorized', array( 'code' => 500 ) );
		} elseif ( 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=archive_error&suffix=tar.gz' === $url ) {
			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}
}
