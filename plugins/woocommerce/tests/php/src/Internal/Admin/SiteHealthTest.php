<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\SiteHealth;
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
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_transient( '_woocommerce_upload_directory_status' );
		parent::tearDown();
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
