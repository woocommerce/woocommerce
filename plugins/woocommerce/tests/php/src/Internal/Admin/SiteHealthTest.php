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

	/**
	 * @testdox Legacy state code remediation stays visible until the configuration is fixed.
	 */
	public function test_legacy_state_code_remediation_is_dynamic_and_links_to_each_setting(): void {
		$original_store_location = get_option( 'woocommerce_default_country', false );
		update_option( 'woocommerce_default_country', 'NP:BAG' );

		try {
			$result = $this->sut->run_test( 'woocommerce_legacy_state_code_configuration' );

			$this->assertSame( 'recommended', $result['status'], 'Legacy Nepal configuration should produce a recommendation.' );
			$this->assertSame( 'State and province settings need attention', $result['label'], 'The recommendation should explain what needs attention.' );
			$this->assertStringContainsString( 'admin.php?page=wc-settings&#038;tab=general', $result['actions'], 'The store-address action should use the general settings URL.' );
			$this->assertStringContainsString( 'admin.php?page=wc-settings&#038;tab=shipping', $result['actions'], 'The shipping action should use the shipping settings URL.' );
			$this->assertStringContainsString( 'admin.php?page=wc-settings&#038;tab=tax', $result['actions'], 'The tax action should use the tax settings URL.' );

			update_option( 'woocommerce_default_country', 'NP:P3' );
			$result = $this->sut->run_test( 'woocommerce_legacy_state_code_configuration' );

			$this->assertSame( 'good', $result['status'], 'The recommendation should clear as soon as the setting is fixed.' );
		} finally {
			if ( false === $original_store_location ) {
				delete_option( 'woocommerce_default_country' );
			} else {
				update_option( 'woocommerce_default_country', $original_store_location );
			}
		}
	}

	/**
	 * @testdox Legacy state code remediation reports database failures as inconclusive.
	 */
	public function test_legacy_state_code_remediation_reports_database_errors(): void {
		global $wpdb;

		$original_prefix = $wpdb->prefix;
		$wpdb->prefix    = 'missing_woocommerce_test_';

		try {
			$result = $this->sut->run_test( 'woocommerce_legacy_state_code_configuration' );

			$this->assertSame( 'recommended', $result['status'], 'Database failures should not report the configuration as clean.' );
			$this->assertSame( 'WooCommerce could not check state and province settings', $result['label'], 'The result should identify an inconclusive database check.' );
			$this->assertStringContainsString( 'A database query failed', $result['description'], 'The result should explain why the check is inconclusive.' );
			$this->assertStringContainsString( 'Review store address', $result['actions'], 'The result should retain remediation links.' );
		} finally {
			$wpdb->prefix = $original_prefix;
			$wpdb->flush();
		}
	}
}
