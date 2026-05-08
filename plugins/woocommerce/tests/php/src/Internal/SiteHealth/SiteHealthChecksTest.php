<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks;
use WC_Unit_Test_Case;

/**
 * SiteHealthChecksTest class.
 */
class SiteHealthChecksTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var SiteHealthChecks
	 */
	private SiteHealthChecks $checks;

	public function setUp(): void {
		parent::setUp();
		$this->checks = wc_get_container()->get( SiteHealthChecks::class );
		$this->checks->register();
	}

	public function tearDown(): void {
		remove_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) );
		parent::tearDown();
	}

	public function test_register_attaches_site_status_tests_filter() {
		// Proves register() actually wired up the callback — has_filter() returns
		// false when the callback is absent, or the integer priority when present.
		$this->assertNotFalse(
			has_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) )
		);
	}

	// -------------------------------------------------------------------------
	// Task 3: Pending DB update check.
	// -------------------------------------------------------------------------

	public function test_pending_db_update_check_critical_when_update_needed() {
		update_option( 'woocommerce_db_version', '0.0.1' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$entry  = $tests['direct']['woocommerce_pending_db_update'] ?? null;
		$this->assertNotNull( $entry );
		$result = call_user_func( $entry['test'] );
		$this->assertSame( 'critical', $result['status'] );
		$this->assertSame( 'woocommerce_pending_db_update', $result['test'] );
	}

	public function test_pending_db_update_check_good_when_up_to_date() {
		update_option( 'woocommerce_db_version', WC()->version );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_pending_db_update']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 4: Required pages check.
	// -------------------------------------------------------------------------

	public function test_required_pages_check_critical_when_page_missing() {
		update_option( 'woocommerce_shop_page_id', 0 );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'critical', $result['status'] );
	}

	public function test_required_pages_check_good_when_all_published() {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
			$page = wp_insert_post( array( 'post_title' => $key, 'post_type' => 'page', 'post_status' => 'publish' ) );
			update_option( "woocommerce_{$key}_page_id", $page );
		}
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 5: HPOS status and Legacy REST API checks.
	// -------------------------------------------------------------------------

	public function test_hpos_check_recommended_when_legacy_storage() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	public function test_hpos_check_recommended_when_sync_enabled() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'yes' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	public function test_hpos_check_good_when_authoritative_no_sync() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	public function test_legacy_rest_api_check_recommended_when_enabled() {
		update_option( 'woocommerce_api_enabled', 'yes' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_legacy_rest_api']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	public function test_legacy_rest_api_check_good_when_disabled() {
		update_option( 'woocommerce_api_enabled', 'no' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_legacy_rest_api']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 6: HTTPS and payment gateway checks.
	// -------------------------------------------------------------------------

	public function test_https_check_critical_when_site_url_not_https() {
		update_option( 'siteurl', 'http://example.test' );
		update_option( 'home', 'http://example.test' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_https']['test'] );
		$this->assertSame( 'critical', $result['status'] );
	}

	public function test_https_check_good_when_site_url_https() {
		update_option( 'siteurl', 'https://example.test' );
		update_option( 'home', 'https://example.test' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_https']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	public function test_payment_gateway_check_recommended_when_none_enabled() {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_cod_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'no' ) );
		WC()->payment_gateways()->init();
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_payment_gateway']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	public function test_payment_gateway_check_good_when_gateway_enabled() {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'yes', 'title' => 'Bank Transfer' ) );
		WC()->payment_gateways()->init();
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_payment_gateway']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

}
