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
	 * @testdox Upload directory protection check fails when the HTTP request fails.
	 */
	public function test_uploads_directory_protection_fails_for_http_request_error(): void {
		$filter_callback = static function ( $_preempt, $_parsed_args, $_url ) {
			unset( $_preempt, $_parsed_args, $_url );

			return new WP_Error( 'http_request_failed', 'Request failed.' );
		};

		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		try {
			$result = $this->sut->run_test( 'woocommerce_uploads_directory_protection' );

			$this->assertSame( 'critical', $result['status'], 'Request failures should not be reported as protected.' );
			$this->assertFalse( get_transient( '_woocommerce_upload_directory_status' ), 'Request failures should not be cached.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}

	/**
	 * @testdox Upload directory protection check fails when the HTTP response code is zero.
	 */
	public function test_uploads_directory_protection_fails_for_zero_response_code(): void {
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

			$this->assertSame( 'critical', $result['status'], 'Missing response codes should not be reported as protected.' );
			$this->assertFalse( get_transient( '_woocommerce_upload_directory_status' ), 'Missing response codes should not be cached.' );
		} finally {
			remove_filter( 'pre_http_request', $filter_callback, 10 );
		}
	}
}
