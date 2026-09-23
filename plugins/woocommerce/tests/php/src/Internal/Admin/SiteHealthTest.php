<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SiteHealth;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use WC_Admin_Notices;
use WC_Unit_Test_Case;
use WP_Error;

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
		delete_transient( '_woocommerce_upload_directory_status' );
		delete_option( 'wc_downloads_approved_directories_mode' );
		WC_Admin_Notices::remove_notice( 'download_directories_sync_complete', true );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_transient( '_woocommerce_upload_directory_status' );
		delete_option( 'wc_downloads_approved_directories_mode' );
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
			$this->assertSame( 'unverified', get_transient( '_woocommerce_upload_directory_status' ), 'Request failures should be cached.' );

			$this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 1, $request_count, 'Cached request failures should not trigger another loopback request.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is inconclusive when the HTTP response code is zero.
	 */
	public function test_uploads_directory_protection_is_inconclusive_for_zero_response_code(): void {
		$request_count   = 0;
		$filter_callback = static function ( $_preempt, $_parsed_args, $_url ) use ( &$request_count ) {
			unset( $_preempt, $_parsed_args, $_url );

			++$request_count;

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
			$this->assertSame( 'unverified', get_transient( '_woocommerce_upload_directory_status' ), 'Missing response codes should be cached.' );

			$this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 1, $request_count, 'Cached missing response codes should not trigger another loopback request.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check is critical when directory browsing is exposed.
	 */
	public function test_uploads_directory_protection_is_critical_when_directory_browsing_is_exposed(): void {
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
			$this->assertSame( 'unprotected', get_transient( '_woocommerce_upload_directory_status' ), 'Browsable uploads directory results should be cached as unprotected.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}
}
