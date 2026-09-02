<?php
declare( strict_types = 1 );

use Automattic\Jetpack\Constants;

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
		unset( $_GET['page'] );
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
		delete_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_SUBSCRIPTIONS );
	}

	/**
	 * Get subscription data containing valid and malformed entries.
	 *
	 * @return array
	 */
	private function get_mixed_subscription_data(): array {
		return array(
			'scalar'              => 'corrupted',
			'missing ID'          => array( 'product_key' => 'missing-id' ),
			'array ID'            => array( 'product_id' => array( 456 ) ),
			'zero ID'             => array( 'product_id' => 0 ),
			'negative ID'         => array( 'product_id' => -10 ),
			'float ID'            => array( 'product_id' => 900001.9 ),
			'decimal string ID'   => array( 'product_id' => '900002.9' ),
			'scientific ID'       => array( 'product_id' => '9e5' ),
			'signed ID'           => array( 'product_id' => '+900003' ),
			'whitespace ID'       => array( 'product_id' => ' 900004 ' ),
			'boolean ID'          => array( 'product_id' => true ),
			'missing connections' => array( 'product_id' => 900005 ),
			'invalid connections' => array(
				'product_id'  => 900006,
				'connections' => 'corrupted',
			),
			'valid integer ID'    => array(
				'product_id'  => 123,
				'product_key' => 'integer-key',
				'connections' => array( 789 ),
				'metadata'    => array( 'preserved' => true ),
			),
			'valid string ID'     => array(
				'product_id'  => '456',
				'product_key' => 'string-key',
				'connections' => array(),
			),
		);
	}

	/**
	 * Get the valid entries from the mixed subscription fixture.
	 *
	 * @return array
	 */
	private function get_valid_subscription_data(): array {
		$data = $this->get_mixed_subscription_data();

		return array(
			'valid integer ID' => $data['valid integer ID'],
			'valid string ID'  => $data['valid string ID'],
		);
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
				'connections' => array(),
			),
		);
		set_transient( '_woocommerce_helper_subscriptions', $valid_data, HOUR_IN_SECONDS );

		$result = WC_Helper::get_subscriptions();

		$this->assertEquals( $valid_data, $result, 'Valid cached data should be returned as-is' );
	}

	/**
	 * @testdox get_subscriptions should filter malformed cached entries without modifying valid subscriptions.
	 */
	public function test_get_subscriptions_filters_malformed_cached_entries(): void {
		set_transient( '_woocommerce_helper_subscriptions', $this->get_mixed_subscription_data(), HOUR_IN_SECONDS );

		$result = WC_Helper::get_subscriptions();

		$this->assertSame( $this->get_valid_subscription_data(), $result, 'Only valid cached subscriptions should be returned unchanged' );
	}

	/**
	 * @testdox get_subscriptions should filter malformed API entries before caching them.
	 */
	public function test_get_subscriptions_filters_malformed_api_entries(): void {
		$response_data         = $this->get_mixed_subscription_data();
		$previous_auth         = WC_Helper_Options::get( 'auth', array() );
		$previous_log          = WC_Helper::$log;
		$had_wp_debug_override = array_key_exists( 'WP_DEBUG', Constants::$set_constants );
		$previous_wp_debug     = Constants::$set_constants['WP_DEBUG'] ?? null;
		$filtered_count        = count( $response_data ) - count( $this->get_valid_subscription_data() );
		$http_mock             = static function () use ( $response_data ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( $response_data ),
			);
		};
		$logger                = $this->createMock( WC_Logger_Interface::class );
		$logger->expects( $this->once() )
			->method( 'log' )
			->with(
				'warning',
				sprintf(
					'Filtered %d malformed subscription entries from the WooCommerce.com API response.',
					$filtered_count
				),
				array( 'source' => 'helper' )
			);
		WC_Helper::$log = $logger;
		Constants::set_constant( 'WP_DEBUG', true );
		WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => 'test-token',
				'access_token_secret' => 'test-secret',
			)
		);
		add_filter( 'pre_http_request', $http_mock );

		try {
			$result = WC_Helper::get_subscriptions();
		} finally {
			remove_filter( 'pre_http_request', $http_mock );
			WC_Helper_Options::update( 'auth', $previous_auth );
			WC_Helper::$log = $previous_log;
			if ( $had_wp_debug_override ) {
				Constants::set_constant( 'WP_DEBUG', $previous_wp_debug );
			} else {
				Constants::clear_single_constant( 'WP_DEBUG' );
			}
		}

		$this->assertSame( $this->get_valid_subscription_data(), $result, 'Only valid API subscriptions should be returned unchanged' );
		$this->assertSame(
			$this->get_valid_subscription_data(),
			get_transient( '_woocommerce_helper_subscriptions' ),
			'Only valid API subscriptions should be cached'
		);
	}

	/**
	 * @testdox get_subscriptions should record a backoff on a 429 without caching an empty subscription list.
	 */
	public function test_get_subscriptions_does_not_cache_empty_list_when_rate_limited(): void {
		$previous_auth = WC_Helper_Options::get( 'auth', array() );
		$previous_log  = WC_Helper::$log;
		$http_mock     = static function () {
			return array(
				'headers'  => array( 'retry-after' => '60' ),
				'response' => array(
					'code'    => 429,
					'message' => 'Too Many Requests',
				),
				'body'     => '{"code":"wccom_rest_limit_reached","data":{"status":429}}',
			);
		};

		WC_Helper::$log = $this->createMock( WC_Logger_Interface::class );
		WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => 'test-token',
				'access_token_secret' => 'test-secret',
			)
		);
		add_filter( 'pre_http_request', $http_mock );

		try {
			$result = WC_Helper::get_subscriptions();
		} finally {
			remove_filter( 'pre_http_request', $http_mock );
			WC_Helper_Options::update( 'auth', $previous_auth );
			WC_Helper::$log = $previous_log;
		}

		$this->assertSame( array(), $result, 'A rate-limited response should yield no subscriptions' );
		$this->assertFalse(
			get_transient( '_woocommerce_helper_subscriptions' ),
			'A 429 should not cache an empty subscription list, which would outlive the backoff window'
		);
		$this->assertNotFalse(
			get_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_SUBSCRIPTIONS ),
			'A 429 should record a backoff window for the subscriptions endpoint'
		);
	}

	/**
	 * @testdox get_subscriptions should cache an empty list for non-rate-limit errors.
	 */
	public function test_get_subscriptions_caches_empty_list_for_other_errors(): void {
		$previous_auth = WC_Helper_Options::get( 'auth', array() );
		$previous_log  = WC_Helper::$log;
		$http_mock     = static function () {
			return array(
				'response' => array(
					'code'    => 500,
					'message' => 'Internal Server Error',
				),
				'body'     => '',
			);
		};

		WC_Helper::$log = $this->createMock( WC_Logger_Interface::class );
		WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => 'test-token',
				'access_token_secret' => 'test-secret',
			)
		);
		add_filter( 'pre_http_request', $http_mock );

		try {
			$result = WC_Helper::get_subscriptions();
		} finally {
			remove_filter( 'pre_http_request', $http_mock );
			WC_Helper_Options::update( 'auth', $previous_auth );
			WC_Helper::$log = $previous_log;
		}

		$this->assertSame( array(), $result, 'A failed response should yield no subscriptions' );
		$this->assertSame(
			array(),
			get_transient( '_woocommerce_helper_subscriptions' ),
			'A non-429 error should still cache an empty subscription list'
		);
		$this->assertFalse(
			get_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_SUBSCRIPTIONS ),
			'Only a 429 should record a backoff window'
		);
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
	 * @testdox get_subscription_list_data should handle malformed subscription entries.
	 */
	public function test_get_subscription_list_data_handles_malformed_entries(): void {
		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				'corrupted',
				array( 'product_key' => 'missing-id' ),
				array( 'product_id' => array( 456 ) ),
				array( 'product_id' => 900005 ),
				array(
					'product_id'  => 900006,
					'connections' => 'corrupted',
				),
			),
			HOUR_IN_SECONDS
		);

		$http_mock = static function () {
			return new WP_Error( 'test', 'Mocked error' );
		};
		add_filter( 'pre_http_request', $http_mock );

		try {
			$result = WC_Helper::get_subscription_list_data();
		} finally {
			remove_filter( 'pre_http_request', $http_mock );
		}

		$this->assertIsArray( $result, 'Malformed subscription entries should not interrupt the Extensions list' );
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
				'connections'           => array(),
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
				'connections'           => array(),
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

	/**
	 * Invoke the private static WC_Helper::get_subscriptions_url().
	 *
	 * @return string
	 */
	private function get_subscriptions_url(): string {
		$method = new ReflectionMethod( WC_Helper::class, 'get_subscriptions_url' );
		$method->setAccessible( true );
		return (string) $method->invoke( null );
	}

	/**
	 * @testdox get_subscriptions_url query contains no stray whitespace (regression: GH #66075).
	 */
	public function test_subscriptions_url_query_has_no_whitespace(): void {
		$_GET['page'] = 'wc-admin';

		$query = wp_parse_url( $this->get_subscriptions_url(), PHP_URL_QUERY );

		$this->assertIsString( $query );
		$this->assertStringNotContainsString( ' ', $query );
		$this->assertStringNotContainsString( '%20', $query );
	}

	/**
	 * @testdox get_subscriptions_url targets the Extensions - My Subscriptions screen.
	 */
	public function test_subscriptions_url_targets_my_subscriptions(): void {
		$this->assertStringEndsWith(
			'admin.php?page=wc-admin&tab=my-subscriptions&path=%2Fextensions',
			$this->get_subscriptions_url()
		);
	}
}
