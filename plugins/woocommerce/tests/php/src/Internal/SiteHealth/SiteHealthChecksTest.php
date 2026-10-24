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

	/**
	 * Set up the system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->checks = wc_get_container()->get( SiteHealthChecks::class );
		$this->checks->register();
	}

	/**
	 * Tear down state registered during the test.
	 */
	public function tearDown(): void {
		remove_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) );
		parent::tearDown();
	}

	/**
	 * Run the `site_status_tests` filter and return the tests WooCommerce registered.
	 *
	 * @return array The combined direct and async Site Health tests.
	 */
	private function get_registered_tests(): array {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Invoking WP core's site_status_tests filter.
		return apply_filters(
			'site_status_tests',
			array(
				'direct' => array(),
				'async'  => array(),
			)
		);
	}

	/**
	 * register() attaches the WooCommerce callback to the site_status_tests filter.
	 */
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

	/**
	 * Pending DB update check is critical when the stored DB version is behind.
	 */
	public function test_pending_db_update_check_critical_when_update_needed() {
		update_option( 'woocommerce_db_version', '0.0.1' );
		$tests = $this->get_registered_tests();
		$entry = $tests['direct']['woocommerce_pending_db_update'] ?? null;
		$this->assertNotNull( $entry );
		$result = call_user_func( $entry['test'] );
		$this->assertSame( 'critical', $result['status'] );
		$this->assertSame( 'woocommerce_pending_db_update', $result['test'] );
	}

	/**
	 * Pending DB update check is good when the stored DB version is current.
	 */
	public function test_pending_db_update_check_good_when_up_to_date() {
		update_option( 'woocommerce_db_version', WC()->version );
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_pending_db_update']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 4: Required pages check.
	// -------------------------------------------------------------------------

	/**
	 * Required pages check is critical when a required page is missing.
	 */
	public function test_required_pages_check_critical_when_page_missing() {
		update_option( 'woocommerce_shop_page_id', 0 );
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'critical', $result['status'] );
	}

	/**
	 * Required pages check is good when all required pages are published.
	 */
	public function test_required_pages_check_good_when_all_published() {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
			$page = wp_insert_post(
				array(
					'post_title'  => $key,
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);
			update_option( "woocommerce_{$key}_page_id", $page );
		}
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 5: HPOS status and Legacy REST API checks.
	// -------------------------------------------------------------------------

	/**
	 * HPOS check is recommended when orders still use legacy post storage.
	 */
	public function test_hpos_check_recommended_when_legacy_storage() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'no' );
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	/**
	 * HPOS check is recommended when HPOS is authoritative but sync is still enabled.
	 */
	public function test_hpos_check_recommended_when_sync_enabled() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'yes' );
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	/**
	 * HPOS check is good when HPOS is authoritative and sync is disabled.
	 */
	public function test_hpos_check_good_when_authoritative_no_sync() {
		update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
		update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_hpos_status']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	/**
	 * Legacy REST API check is recommended when the API is enabled.
	 *
	 * Core force-reads `woocommerce_api_enabled` through a `pre_option` filter
	 * (see wc-deprecated-functions.php), so update_option() has no effect. Drive
	 * the value with a higher-priority `pre_option` filter instead.
	 */
	public function test_legacy_rest_api_check_recommended_when_enabled() {
		$force_value = static function () {
			return 'yes';
		};
		add_filter( 'pre_option_woocommerce_api_enabled', $force_value, 99 );

		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_legacy_rest_api']['test'] );

		remove_filter( 'pre_option_woocommerce_api_enabled', $force_value, 99 );
		$this->assertSame( 'recommended', $result['status'] );
	}

	/**
	 * Legacy REST API check is good when the API is disabled.
	 */
	public function test_legacy_rest_api_check_good_when_disabled() {
		$force_value = static function () {
			return 'no';
		};
		add_filter( 'pre_option_woocommerce_api_enabled', $force_value, 99 );

		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_legacy_rest_api']['test'] );

		remove_filter( 'pre_option_woocommerce_api_enabled', $force_value, 99 );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 6: HTTPS and payment gateway checks.
	// -------------------------------------------------------------------------

	/**
	 * HTTPS check is critical when the store home URL does not use HTTPS.
	 *
	 * The test environment fixes the real `home` option, so it is driven with a
	 * `pre_option_home` filter rather than update_option().
	 */
	public function test_https_check_critical_when_site_url_not_https() {
		$force_home = static function () {
			return 'http://example.test';
		};
		add_filter( 'pre_option_home', $force_home );

		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_https']['test'] );

		remove_filter( 'pre_option_home', $force_home );
		$this->assertSame( 'critical', $result['status'] );
	}

	/**
	 * HTTPS check is good when the store home URL uses HTTPS.
	 *
	 * The test environment fixes the real `home` option, so it is driven with a
	 * `pre_option_home` filter rather than update_option().
	 */
	public function test_https_check_good_when_site_url_https() {
		$force_home = static function () {
			return 'https://example.test';
		};
		add_filter( 'pre_option_home', $force_home );

		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_https']['test'] );

		remove_filter( 'pre_option_home', $force_home );
		$this->assertSame( 'good', $result['status'] );
	}

	/**
	 * Payment gateway check is recommended when no gateway is enabled.
	 */
	public function test_payment_gateway_check_recommended_when_none_enabled() {
		update_option( 'woocommerce_bacs_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_cod_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'no' ) );
		WC()->payment_gateways()->init();
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_payment_gateway']['test'] );
		$this->assertSame( 'recommended', $result['status'] );
	}

	/**
	 * Payment gateway check is good when at least one gateway is enabled.
	 */
	public function test_payment_gateway_check_good_when_gateway_enabled() {
		update_option(
			'woocommerce_bacs_settings',
			array(
				'enabled' => 'yes',
				'title'   => 'Bank Transfer',
			)
		);
		WC()->payment_gateways()->init();
		$tests  = $this->get_registered_tests();
		$result = call_user_func( $tests['direct']['woocommerce_payment_gateway']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}
}
