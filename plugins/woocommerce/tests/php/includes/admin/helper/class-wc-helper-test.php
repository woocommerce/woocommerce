<?php
declare( strict_types = 1 );

/**
 * Class WC_Tests_WC_Helper.
 */
class WC_Helper_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->cleanup_helper_transients();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->cleanup_helper_transients();
		parent::tearDown();
	}

	/**
	 * Clean up transients used by WC_Helper.
	 */
	private function cleanup_helper_transients(): void {
		delete_transient( '_woocommerce_helper_subscriptions' );
		delete_transient( '_woocommerce_helper_product_usage_notice_rules' );
		delete_transient( '_woocommerce_helper_notices' );
		delete_transient( '_woocommerce_helper_connection_data' );
	}

	/**
	 * @testdox get_subscriptions should delete corrupted string transient and return empty array.
	 */
	public function test_get_subscriptions_handles_corrupted_string_transient(): void {
		set_transient( '_woocommerce_helper_subscriptions', 'corrupted_string_data', HOUR_IN_SECONDS );

		// Mock API to prevent actual network call - return WP_Error to trigger empty array return.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Helper::get_subscriptions();

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertIsArray( $result, 'Result should be an array even when transient was corrupted' );
		$this->assertEmpty( $result, 'Result should be empty array on API error' );

		// Verify corrupted string is no longer in transient (replaced with empty array).
		$transient_value = get_transient( '_woocommerce_helper_subscriptions' );
		$this->assertNotEquals( 'corrupted_string_data', $transient_value, 'Corrupted string transient should have been replaced' );
		$this->assertIsArray( $transient_value, 'Transient should now be an array' );
	}

	/**
	 * @testdox get_subscriptions should return valid cached array without modification.
	 */
	public function test_get_subscriptions_returns_valid_cached_array(): void {
		$valid_data = array(
			array(
				'product_id'  => 123,
				'product_key' => 'test_key',
			),
		);
		set_transient( '_woocommerce_helper_subscriptions', $valid_data, HOUR_IN_SECONDS );

		$result = WC_Helper::get_subscriptions();

		$this->assertEquals( $valid_data, $result, 'Valid cached data should be returned as-is' );
	}

	/**
	 * @testdox get_cached_connection_data should return false for corrupted string transient.
	 */
	public function test_get_cached_connection_data_handles_corrupted_string_transient(): void {
		set_transient( '_woocommerce_helper_connection_data', 'corrupted_string', HOUR_IN_SECONDS );

		$result = WC_Helper::get_cached_connection_data();

		$this->assertFalse( $result, 'Corrupted transient should return false' );
		$this->assertFalse( get_transient( '_woocommerce_helper_connection_data' ), 'Corrupted transient should be deleted' );
	}

	/**
	 * @testdox get_cached_connection_data should return valid cached array.
	 */
	public function test_get_cached_connection_data_returns_valid_array(): void {
		$valid_data = array( 'url' => 'https://example.com' );
		set_transient( '_woocommerce_helper_connection_data', $valid_data, HOUR_IN_SECONDS );

		$result = WC_Helper::get_cached_connection_data();

		$this->assertEquals( $valid_data, $result, 'Valid cached array should be returned' );
	}

	/**
	 * @testdox get_cached_connection_data should return false when transient does not exist.
	 */
	public function test_get_cached_connection_data_returns_false_for_missing_transient(): void {
		delete_transient( '_woocommerce_helper_connection_data' );

		$result = WC_Helper::get_cached_connection_data();

		$this->assertFalse( $result, 'Missing transient should return false' );
	}

	/**
	 * @testdox get_product_usage_notice_rules should delete corrupted transient and fetch fresh data.
	 */
	public function test_get_product_usage_notice_rules_handles_corrupted_transient(): void {
		set_transient( '_woocommerce_helper_product_usage_notice_rules', 'corrupted_data', HOUR_IN_SECONDS );

		// Mock API to return empty array.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Helper::get_product_usage_notice_rules();

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertIsArray( $result, 'Result should be an array' );
	}

	/**
	 * @testdox get_notices should delete corrupted transient and return empty array.
	 */
	public function test_get_notices_handles_corrupted_transient(): void {
		set_transient( '_woocommerce_helper_notices', 'corrupted_data', HOUR_IN_SECONDS );

		// Mock API to return non-200 response.
		$http_mock = function () {
			return array(
				'response' => array( 'code' => 500 ),
				'body'     => '',
			);
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Helper::get_notices();

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertEmpty( $result, 'Result should be empty on API failure' );
	}

	/**
	 * @testdox get_subscription_list_data should handle non-array subscriptions gracefully.
	 */
	public function test_get_subscription_list_data_handles_non_array_subscriptions(): void {
		set_transient( '_woocommerce_helper_subscriptions', 'corrupted', HOUR_IN_SECONDS );

		// Mock API to prevent network call.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Helper::get_subscription_list_data();

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertIsArray( $result, 'Result should be an array even with corrupted subscriptions transient' );
	}

	/**
	 * @testdox get_installed_subscriptions should return empty array when subscriptions are corrupted.
	 */
	public function test_get_installed_subscriptions_handles_corrupted_subscriptions(): void {
		set_transient( '_woocommerce_helper_subscriptions', 'corrupted', HOUR_IN_SECONDS );

		// Mock API to prevent network call.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		// Set up auth to avoid early return.
		WC_Helper_Options::update( 'auth', array( 'site_id' => 12345 ) );

		$result = WC_Helper::get_installed_subscriptions();

		remove_filter( 'pre_http_request', $http_mock );
		WC_Helper_Options::update( 'auth', array() );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertEmpty( $result, 'Result should be empty when subscriptions are corrupted' );
	}

	/**
	 * @testdox get_subscription should return false when subscriptions are corrupted.
	 */
	public function test_get_subscription_handles_corrupted_subscriptions(): void {
		set_transient( '_woocommerce_helper_subscriptions', 'corrupted', HOUR_IN_SECONDS );

		// Mock API to prevent network call.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Helper::get_subscription( 'some_product_key' );

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertFalse( $result, 'Result should be false when subscriptions are corrupted' );
	}

	/**
	 * @testdox has_host_plan_orders should return false when subscriptions are corrupted.
	 */
	public function test_has_host_plan_orders_handles_corrupted_subscriptions(): void {
		set_transient( '_woocommerce_helper_subscriptions', 'corrupted', HOUR_IN_SECONDS );

		// Mock API to prevent network call.
		$http_mock = function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		$result = WC_Woo_Helper_Connection::has_host_plan_orders();

		remove_filter( 'pre_http_request', $http_mock );

		$this->assertFalse( $result, 'Should return false when subscriptions are corrupted' );
	}

	/**
	 * @testdox has_host_plan_orders should return true when subscription has host plan.
	 */
	public function test_has_host_plan_orders_returns_true_for_host_plan(): void {
		$subscriptions = array(
			array(
				'product_id'            => 123,
				'included_in_host_plan' => true,
			),
		);
		set_transient( '_woocommerce_helper_subscriptions', $subscriptions, HOUR_IN_SECONDS );

		$result = WC_Woo_Helper_Connection::has_host_plan_orders();

		$this->assertTrue( $result, 'Should return true when subscription has host plan' );
	}

	/**
	 * @testdox has_host_plan_orders should return false when no subscription has host plan.
	 */
	public function test_has_host_plan_orders_returns_false_without_host_plan(): void {
		$subscriptions = array(
			array(
				'product_id'            => 123,
				'included_in_host_plan' => false,
			),
		);
		set_transient( '_woocommerce_helper_subscriptions', $subscriptions, HOUR_IN_SECONDS );

		$result = WC_Woo_Helper_Connection::has_host_plan_orders();

		$this->assertFalse( $result, 'Should return false when no subscription has host plan' );
	}

	/**
	 * Test that woo plugins are loaded correctly even if incorrect cache is initially set.
	 */
	public function test_get_local_woo_plugins_without_woo_header_cache() {
		$woocommerce_key = 'sample-woo-plugin.php';

		remove_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );
		wp_clean_plugins_cache( false );
		get_plugins();

		if ( file_exists( WP_PLUGIN_DIR . '/sample-woo-plugin.php' ) ) {
			unlink( WP_PLUGIN_DIR . '/sample-woo-plugin.php' );
		}
		copy( \WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-woo-plugin.php', WP_PLUGIN_DIR . '/sample-woo-plugin.php' );

		add_filter( 'extra_plugin_headers', 'wc_enable_wc_plugin_headers' );

		$woo_plugins = \WC_Helper::get_local_woo_plugins();

		// Restore previous state.
		wp_clean_plugins_cache( false );

		$this->assertArrayHasKey( $woocommerce_key, $woo_plugins );
	}

}
