<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SiteHealth;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use WC_Admin_Notices;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * Tests for the SiteHealth class.
 */
class SiteHealthTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var SiteHealth
	 */
	private SiteHealth $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new SiteHealth();
		delete_option( 'wc_downloads_approved_directories_mode' );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'wc_downloads_approved_directories_mode' );
		delete_option( 'woocommerce_file_download_method' );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
		parent::tearDown();
	}

	/**
	 * @testdox Approved download directories check is good when rules are enforced.
	 */
	public function test_approved_download_directories_check_is_good_when_rules_are_enforced(): void {
		wc_get_container()->get( Download_Directories::class )->set_mode( Download_Directories::MODE_ENABLED );

		$result = $this->sut->run_test( 'woocommerce_approved_download_directories_enforcement' );

		$this->assertSame( 'good', $result['status'], 'Enabled approved directory rules should pass the Site Health check.' );
		$this->assertSame( 'WooCommerce approved download directory rules are enforced', $result['label'], 'The result should report that approved directory rules are enforced.' );
	}

	/**
	 * @testdox Approved download directories check recommends enabling rules when they are not enforced.
	 */
	public function test_approved_download_directories_check_recommends_enabling_rules_when_not_enforced(): void {
		$result = $this->sut->run_test( 'woocommerce_approved_download_directories_enforcement' );

		$this->assertSame( 'recommended', $result['status'], 'Disabled approved directory rules should require attention in Site Health.' );
		$this->assertSame( 'WooCommerce approved download directory rules are not enforced', $result['label'], 'The result should report that approved directory rules are not enforced.' );
		$this->assertSame( '<p>Enable approved download directory rules to control which local and remote locations can be used for downloadable product files. This reduces the risk of exposing unintended files or connecting to unapproved remote locations.</p>', $result['description'], 'The result should explain why approved directory rules should be enabled.' );
		$this->assertStringContainsString( 'section=download_urls', $result['actions'], 'The result should link to the approved directory settings.' );
	}

	/**
	 * @testdox Approved download directories check recommends review after synchronization when rules are enforced.
	 */
	public function test_approved_download_directories_check_recommends_review_after_synchronization(): void {
		wc_get_container()->get( Download_Directories::class )->set_mode( Download_Directories::MODE_ENABLED );
		WC_Admin_Notices::add_notice( 'download_directories_sync_complete', true );

		$result = $this->sut->run_test( 'woocommerce_approved_download_directories_sync' );

		$this->assertSame( 'recommended', $result['status'], 'A completed synchronization should require review even when approved directory rules are enforced.' );
		$this->assertSame( 'WooCommerce approved download directories need review', $result['label'], 'The result should prompt the merchant to review synchronized directories.' );
		$this->assertSame( '<p>Approved product download directory synchronization has completed. Review the list to confirm downloadable product files remain protected.</p>', $result['description'], 'The result should describe synchronization completion without claiming that new directories were found.' );
		$this->assertStringContainsString( 'section=download_urls', $result['actions'], 'The result should link to the approved directory settings.' );
		$this->assertStringContainsString( 'wc-hide-notice=download_directories_sync_complete', $result['actions'], 'The result should let the merchant mark the synchronization as reviewed.' );
	}

	/**
	 * @testdox Approved download directories check is good when no synchronization is waiting for review.
	 */
	public function test_approved_download_directories_check_is_good_without_completed_synchronization(): void {
		$result = $this->sut->run_test( 'woocommerce_approved_download_directories_sync' );

		$this->assertSame( 'good', $result['status'], 'No completed synchronization should require review.' );
		$this->assertSame( 'WooCommerce approved download directories do not require review', $result['label'], 'The result should confirm that synchronized directories do not require review.' );
		$this->assertSame( '<p>There is no completed approved download directory synchronization waiting for review.</p>', $result['description'], 'The result should confirm that no synchronization review is pending.' );
	}

	/**
	 * @testdox Upload directory protection check is inconclusive when the HTTP request fails.
	 */
	public function test_uploads_directory_protection_is_inconclusive_for_http_request_error(): void {
		$request_count   = 0;
		$filter_callback = static function ( $_preempt, $_parsed_args, $_url ) use ( &$request_count ) {
			unset( $_preempt, $_parsed_args, $_url );

			++$request_count;

			return new WP_Error( 'http_request_failed', 'Request failed.' );
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'recommended', $result['status'], 'Request failures should not be reported as a confirmed security failure.' );
			$this->assertSame( 'WooCommerce could not verify uploads directory protection', $result['label'], 'Request failures should report that the result could not be verified.' );

			$this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 2, $request_count, 'Each run should check again instead of reusing an earlier result.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is inconclusive when the HTTP response code is zero.
	 */
	public function test_uploads_directory_protection_is_inconclusive_for_zero_response_code(): void {
		$filter_callback = static function ( $_preempt, $_parsed_args, $_url ) {
			unset( $_preempt, $_parsed_args, $_url );

			return array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 0,
					'message' => '',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'recommended', $result['status'], 'Missing response codes should not be reported as a confirmed security failure.' );
			$this->assertSame( 'WooCommerce could not verify uploads directory protection', $result['label'], 'Missing response codes should report that the result could not be verified.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is critical when directory browsing is exposed with the redirect download method.
	 */
	public function test_uploads_directory_protection_is_critical_when_directory_browsing_is_exposed(): void {
		update_option( 'woocommerce_file_download_method', 'redirect' );

		$filter_callback = static function ( $_preempt, $_parsed_args, $_url ) {
			unset( $_preempt, $_parsed_args, $_url );

			return array(
				'headers'  => array(),
				'body'     => '<html><body>Index of /woocommerce_uploads/</body></html>',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'critical', $result['status'], 'Browsable uploads directories should remain critical.' );
			$this->assertSame( 'WooCommerce uploads directory is browsable from the web', $result['label'], 'Browsable uploads directories should keep the confirmed security failure label.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is critical when the web server serves uploaded files directly.
	 */
	public function test_uploads_directory_protection_is_critical_when_files_are_served_directly(): void {
		$uploads         = wp_get_upload_dir();
		$requested_paths = array();
		$filter_callback = static function ( $_preempt, $_parsed_args, $url ) use ( $uploads, &$requested_paths ) {
			unset( $_preempt, $_parsed_args );

			$path              = str_replace( $uploads['baseurl'], $uploads['basedir'], $url );
			$requested_paths[] = $path;

			return array(
				'headers'  => array(),
				'body'     => file_get_contents( $path ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'critical', $result['status'], 'Directly downloadable files should be a confirmed security failure.' );
			$this->assertSame( 'Downloadable product files can be accessed directly from the web', $result['label'], 'Directly downloadable files should report that files are exposed.' );
			$this->assertStringStartsWith( $uploads['basedir'] . '/woocommerce_uploads/', $requested_paths[0], 'The check should request a file in the WooCommerce uploads directory.' );
			$this->assertFileDoesNotExist( $requested_paths[0], 'The temporary file should be deleted after the request.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is good when only files in the WooCommerce uploads directory are blocked.
	 * @testWith [403, "Forbidden"]
	 *           [200, "<html><body>Log in</body></html>"]
	 *
	 * @param int    $response_code Response code returned for the file in the WooCommerce uploads directory.
	 * @param string $response_body Response body returned for the file in the WooCommerce uploads directory.
	 */
	public function test_uploads_directory_protection_is_good_when_files_are_not_served( int $response_code, string $response_body ): void {
		$uploads         = wp_get_upload_dir();
		$filter_callback = static function ( $_preempt, $_parsed_args, $url ) use ( $uploads, $response_code, $response_body ) {
			unset( $_preempt, $_parsed_args );

			$is_blocked = false !== strpos( $url, '/woocommerce_uploads/' );

			return array(
				'headers'  => array(),
				'body'     => $is_blocked ? $response_body : file_get_contents( str_replace( $uploads['baseurl'], $uploads['basedir'], $url ) ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				'response' => array(
					'code'    => $is_blocked ? $response_code : 200,
					'message' => '',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'good', $result['status'], 'Files that are not served should be reported as protected.' );
			$this->assertStringContainsString( 'Downloadable product files cannot be accessed directly from the web.', $result['description'], 'The protected description should refer to file access.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is inconclusive when no uploaded file is served.
	 */
	public function test_uploads_directory_protection_is_inconclusive_when_no_uploads_are_served(): void {
		$uploads         = wp_get_upload_dir();
		$requested_urls  = array();
		$filter_callback = static function ( $_preempt, $_parsed_args, $url ) use ( &$requested_urls ) {
			unset( $_preempt, $_parsed_args );

			$requested_urls[] = $url;

			return array(
				'headers'  => array(),
				'body'     => 'Unauthorized',
				'response' => array(
					'code'    => 401,
					'message' => 'Unauthorized',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'recommended', $result['status'], 'A site that blocks every upload should not be reported as protected.' );
			foreach ( $requested_urls as $url ) {
				$this->assertFileDoesNotExist( str_replace( $uploads['baseurl'], $uploads['basedir'], $url ), 'Each temporary file should be deleted after its request.' );
			}
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is registered to run asynchronously through the REST API.
	 */
	public function test_uploads_directory_protection_is_registered_as_async_test(): void {
		$tests = $this->sut->handle_site_status_tests( array() );
		$test  = $tests['async']['woocommerce_uploads_directory_protection'] ?? array();

		$this->assertArrayNotHasKey( 'woocommerce_uploads_directory_protection', $tests['direct'], 'The check should not run while the page renders.' );
		$this->assertTrue( $test['has_rest'] ?? false, 'Site Health should run the check through the REST API.' );
		$this->assertSame( rest_url( 'wc-site-health/v1/tests/woocommerce-uploads-directory-protection' ), $test['test'] ?? null, 'The test should point to its REST API route.' );
		$this->assertTrue( $test['skip_cron'] ?? false, 'The scheduled Site Health check should not write test files.' );
	}

	/**
	 * @testdox Upload directory protection REST API route returns the result only to users who can view Site Health checks.
	 */
	public function test_uploads_directory_protection_rest_route_requires_site_health_access(): void {
		$filter_callback = static function () {
			return new WP_Error( 'http_request_failed', 'Request failed.' );
		};
		add_filter( 'pre_http_request', $filter_callback );
		$this->create_rest_server_with_routes( array( array( $this->sut, 'register_async_test_routes' ) ) );

		try {
			$request = new WP_REST_Request( 'GET', '/wc-site-health/v1/tests/woocommerce-uploads-directory-protection' );

			wp_set_current_user( 0 );
			$this->assertSame( 401, rest_do_request( $request )->get_status(), 'Visitors should not be able to run the check.' );

			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			$response = rest_do_request( $request );
			$this->assertSame( 200, $response->get_status(), 'Users who can view Site Health checks should get the result.' );
			$this->assertSame( 'woocommerce_uploads_directory_protection', $response->get_data()['test'] ?? null, 'The response should be the Site Health result.' );

			$result_filter = static function ( $result ) {
				$result['label'] = 'Filtered label';
				return $result;
			};
			add_filter( 'site_status_test_result', $result_filter );
			$this->assertSame( 'Filtered label', rest_do_request( $request )->get_data()['label'] ?? null, 'The site_status_test_result filter should still apply.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback );
			remove_filter( 'site_status_test_result', $result_filter ?? '__return_null' );
			$this->clear_rest_server();
		}
	}

	/**
	 * @testdox Upload directory protection check is inconclusive when the temporary file cannot be written.
	 */
	public function test_uploads_directory_protection_is_inconclusive_when_file_cannot_be_written(): void {
		$request_count       = 0;
		$http_callback       = static function () use ( &$request_count ) {
			++$request_count;

			return new WP_Error( 'http_request_failed', 'Unexpected request.' );
		};
		$upload_dir_callback = static function ( $upload_dir ) {
			$upload_dir['basedir'] .= '/wc-missing-directory';

			return $upload_dir;
		};

		add_filter( 'pre_http_request', $http_callback );
		add_filter( 'upload_dir', $upload_dir_callback );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'recommended', $result['status'], 'A file that cannot be written should not be reported as a confirmed security failure.' );
			$this->assertSame( 'WooCommerce could not verify uploads directory protection', $result['label'], 'A file that cannot be written should report that the result could not be verified.' );
			$this->assertSame( 0, $request_count, 'No request should be made without a temporary file.' );
		} finally {
			remove_filter( 'pre_http_request', $http_callback );
			remove_filter( 'upload_dir', $upload_dir_callback );
		}
	}
}
