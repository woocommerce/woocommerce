<?php
/**
 * Unit tests for WC_Helper_Updater class
 *
 * @package WooCommerce\Tests\Admin\Helper
 */

declare(strict_types=1);

/**
 * Class WC_Helper_Updater_Test
 */
class WC_Helper_Updater_Test extends WC_Unit_Test_Case {
	/**
	 * The mocked response for 'update-check' API used for the tests.
	 *
	 * @var array
	 */
	private $mocked_updates = array(
		123 => array(
			'version'        => '2.0.0',
			'url'            => 'https://woocommerce.com/products/test',
			'package'        => 'https://woocommerce.com/package.zip',
			'slug'           => 'test-plugin',
			'upgrade_notice' => 'New version available',
		),
	);

	/**
	 * Products sent in the mocked update-check request.
	 *
	 * @var array|null
	 */
	private $mocked_request_products;

	/**
	 * Fixture theme root, when a Woo theme has been mocked.
	 *
	 * @var string|null
	 */
	private $theme_root;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->cleanup_transients();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$this->cleanup_transients();
		$this->cleanup_theme_fixture();

		wp_cache_delete( 'plugins', 'plugins' );
		remove_all_filters( 'update_woo_com_subscription_details' );

		parent::tearDown();
	}

	/**
	 * Unregister and delete the fixture theme, when one was created.
	 */
	private function cleanup_theme_fixture() {
		if ( null === $this->theme_root ) {
			return;
		}

		// register_theme_directory() has no counterpart, so the fixture root is removed by hand.
		global $wp_theme_directories;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unregistering the fixture theme root.
		$wp_theme_directories = array_values( array_diff( (array) $wp_theme_directories, array( $this->theme_root ) ) );
		wp_clean_themes_cache();

		$theme_dir = $this->theme_root . '/woo-test-theme';
		foreach ( array( $theme_dir . '/style.css', $theme_dir . '/index.php' ) as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
		foreach ( array( $theme_dir, $this->theme_root ) as $dir ) {
			if ( is_dir( $dir ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture in the temp directory.
				rmdir( $dir );
			}
		}

		$this->theme_root = null;
	}

	/**
	 * Clean up transients used by WC_Helper_Updater.
	 */
	private function cleanup_transients() {
		delete_transient( '_woocommerce_helper_updates' );
		delete_transient( '_woocommerce_helper_updates_count' );
		delete_transient( '_woocommerce_helper_subscriptions' );
		delete_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK );
	}

	/**
	 * Helper method to call private _update_check method via reflection.
	 *
	 * @param array $payload The payload to pass to _update_check.
	 * @return array The result from _update_check.
	 */
	private function call_update_check( $payload ) {
		$reflection = new ReflectionClass( 'WC_Helper_Updater' );
		$method     = $reflection->getMethod( '_update_check' );
		$method->setAccessible( true );

		return $method->invoke( null, $payload );
	}

	/**
	 * @testdox Update-data entry points skip malformed subscription records.
	 *
	 * @dataProvider malformed_subscription_entry_points
	 *
	 * @param string $entry_point Updater method to test.
	 */
	public function test_update_data_entry_points_skip_malformed_subscriptions( string $entry_point ): void {
		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				'corrupted',
				array( 'product_key' => 'missing-id' ),
				array( 'product_id' => array( 456 ) ),
				array( 'product_id' => 0 ),
				array( 'product_id' => -10 ),
				array( 'product_id' => 900001.9 ),
				array( 'product_id' => '900002.9' ),
				array( 'product_id' => '9e5' ),
				array( 'product_id' => '+900003' ),
				array( 'product_id' => ' 900004 ' ),
				array( 'product_id' => true ),
				array( 'product_id' => 900005 ),
				array(
					'product_id'  => 900006,
					'connections' => 'corrupted',
				),
				array(
					'product_id'  => 123,
					'connections' => array(),
				),
				array(
					'product_id'  => '456',
					'connections' => array(),
				),
			),
			HOUR_IN_SECONDS
		);
		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		try {
			$result = call_user_func( array( WC_Helper_Updater::class, $entry_point ) );
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$this->assertSame( $this->mocked_updates, $result, 'Malformed subscriptions should not interrupt the update check' );
		$this->assertIsArray( $this->mocked_request_products, 'The valid subscription should trigger an update-check request' );
		$this->assertSame(
			array( 123, 456 ),
			array_values(
				array_intersect(
					array( 123, 456, 900000, 900001, 900002, 900003, 900004, 900005, 900006 ),
					array_keys( $this->mocked_request_products )
				)
			),
			'Only valid test subscription IDs should be included in the request'
		);
		$this->assertSame(
			456,
			$this->mocked_request_products[456]['product_id'],
			'String subscription IDs should be normalized to integers in the update request'
		);
	}

	/**
	 * Data provider for subscription update entry points.
	 *
	 * @return array
	 */
	public function malformed_subscription_entry_points() {
		return array(
			'available extension downloads' => array( 'get_available_extensions_downloads_data' ),
			'all extension updates'         => array( 'get_update_data' ),
		);
	}

	/**
	 * Helper method to call private should_use_cached_update_data method via reflection.
	 *
	 * @param mixed  $data The cached data to validate.
	 * @param string $hash The expected hash.
	 * @return bool The result from should_use_cached_update_data.
	 */
	private function call_should_use_cached_update_data( $data, $hash ) {
		$reflection = new ReflectionClass( 'WC_Helper_Updater' );
		$method     = $reflection->getMethod( 'should_use_cached_update_data' );
		$method->setAccessible( true );

		return $method->invoke( null, $data, $hash );
	}

	/**
	 * Test that _update_check handles malformed transient data (i.e. string instead of array).
	 */
	public function test_update_check_handles_malformed_string_transient() {
		set_transient( '_woocommerce_helper_updates', 'malformed_string_data', HOUR_IN_SECONDS );

		// Mock WC_Helper and WC_Helper_API to avoid external dependencies.
		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		$payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		$result = $this->call_update_check( $payload );

		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		$this->assertIsArray( $result, 'Result should be an array even when transient was malformed' );
		$this->assertEquals( $this->mocked_updates, $result, 'Result should match mocked updates' );
	}

	/**
	 * Test that _update_check handles valid cached data with matching hash.
	 */
	public function test_update_check_returns_cached_data_with_matching_hash() {
		$payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		ksort( $payload );
		$hash = md5( wp_json_encode( $payload ) );

		$cached_data = array(
			'hash'     => $hash,
			'updated'  => time(),
			'products' => array(
				123 => array(
					'version'        => '1.2.3',
					'url'            => 'https://woocommerce.com/products/test',
					'package'        => 'https://woocommerce.com/package.zip',
					'slug'           => 'test-plugin',
					'upgrade_notice' => 'Test upgrade notice',
				),
			),
			'errors'   => array(),
		);

		set_transient( '_woocommerce_helper_updates', $cached_data, HOUR_IN_SECONDS );

		// Should return cached products without making API call.
		$result = $this->call_update_check( $payload );

		$this->assertEquals( $cached_data['products'], $result, 'Result should match cached version' );
	}

	/**
	 * @testdox A rate-limited update check should keep the cached products instead of caching the empty result.
	 */
	public function test_update_check_preserves_cache_when_rate_limited(): void {
		$cached_data = array(
			'hash'     => 'a-stale-hash',
			'updated'  => time(),
			'products' => array(
				123 => array(
					'version' => '1.2.3',
					'slug'    => 'test-plugin',
				),
			),
			'errors'   => array(),
		);

		set_transient( '_woocommerce_helper_updates', $cached_data, HOUR_IN_SECONDS );

		$http_mock = static function () {
			return array(
				'headers'  => array( 'retry-after' => '60' ),
				'response' => array(
					'code'    => 429,
					'message' => 'Too Many Requests',
				),
				'body'     => '{"code":"wccom_rest_limit_reached","data":{"status":429}}',
			);
		};
		add_filter( 'pre_http_request', $http_mock );

		try {
			$result = $this->call_update_check(
				array(
					123 => array(
						'product_id' => 123,
						'file_id'    => 'abc123',
					),
				)
			);
		} finally {
			remove_filter( 'pre_http_request', $http_mock );
		}

		$this->assertSame( $cached_data['products'], $result, 'A rate-limited check should serve the previously cached products' );
		$this->assertSame(
			$cached_data,
			get_transient( '_woocommerce_helper_updates' ),
			'A 429 should leave the cached update data untouched rather than replacing it with an empty result'
		);
		$this->assertNotFalse(
			get_transient( WC_Helper_API_Backoff::TRANSIENT_PREFIX . WC_Helper_API_Backoff::REQUEST_TYPE_UPDATE_CHECK ),
			'A 429 should record a backoff window for the update-check endpoint'
		);
	}

	/**
	 * Test that _update_check refreshes cache when hash doesn't match.
	 */
	public function test_update_check_refreshes_cache_with_mismatched_hash() {
		$old_payload = array(
			456 => array(
				'product_id' => 456,
				'file_id'    => 'old456',
			),
		);

		ksort( $old_payload );
		$old_hash = md5( wp_json_encode( $old_payload ) );

		$cached_data = array(
			'hash'     => $old_hash,
			'updated'  => time(),
			'products' => array(
				456 => array(
					'version' => '1.0.0',
				),
			),
			'errors'   => array(),
		);

		set_transient( '_woocommerce_helper_updates', $cached_data, HOUR_IN_SECONDS );

		// Mock API response for new payload.
		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		$new_payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		$result = $this->call_update_check( $new_payload );

		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		// Should have made new API call and returned fresh data.
		$this->assertEquals( $this->mocked_updates, $result, 'Result should match mocked updates' );
	}

	/**
	 * Test that _update_check handles false transient (cache miss).
	 */
	public function test_update_check_handles_false_transient() {
		// Ensure transient is false (cache miss).
		delete_transient( '_woocommerce_helper_updates' );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		$payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		$result = $this->call_update_check( $payload );

		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		// Should have made new API call and returned fresh data.
		$this->assertEquals( $this->mocked_updates, $result, 'Result should match mocked updates' );
	}

	/**
	 * Test that _update_check handles empty payload.
	 */
	public function test_update_check_handles_empty_payload() {
		$result = $this->call_update_check( array() );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertEmpty( $result, 'Result should be empty for empty payload' );
	}

	/**
	 * Test that _update_check handles numeric transient data (edge case).
	 */
	public function test_update_check_handles_numeric_transient() {
		// Set up transient with numeric value.
		set_transient( '_woocommerce_helper_updates', 12345, HOUR_IN_SECONDS );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		$payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		// Should not throw error.
		$result = $this->call_update_check( $payload );

		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		// Should have made new API call and returned fresh data.
		$this->assertEquals( $this->mocked_updates, $result, 'Result should match mocked updates' );
	}

	/**
	 * Test that _update_check handles null transient data (edge case).
	 */
	public function test_update_check_handles_null_transient() {
		// Set up transient with null value (though WordPress would typically convert to false).
		set_transient( '_woocommerce_helper_updates', null, HOUR_IN_SECONDS );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );

		$payload = array(
			123 => array(
				'product_id' => 123,
				'file_id'    => 'abc123',
			),
		);

		// Should not throw error.
		$result = $this->call_update_check( $payload );

		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		// Should have made new API call and returned fresh data.
		$this->assertEquals( $this->mocked_updates, $result, 'Result should match mocked updates' );
	}

	/**
	 * Test that flush_updates_cache clears all relevant transients.
	 */
	public function test_flush_updates_cache_clears_transients() {
		// Set up transients.
		set_transient( '_woocommerce_helper_updates', array( 'test' => 'data' ), HOUR_IN_SECONDS );
		set_transient( '_woocommerce_helper_updates_count', 5, HOUR_IN_SECONDS );

		// Verify transients are set.
		$this->assertNotFalse( get_transient( '_woocommerce_helper_updates' ), 'Updates transient should be set' );
		$this->assertNotFalse( get_transient( '_woocommerce_helper_updates_count' ), 'Count transient should be set' );

		// Flush cache.
		WC_Helper_Updater::flush_updates_cache();

		// Verify transients are cleared.
		$this->assertFalse( get_transient( '_woocommerce_helper_updates' ), 'Updates transient should be cleared' );
		$this->assertFalse( get_transient( '_woocommerce_helper_updates_count' ), 'Count transient should be cleared' );
	}

	/**
	 * Test that upgrader_process_complete clears the count transient.
	 */
	public function test_upgrader_process_complete_clears_count_transient() {
		// Set up count transient.
		set_transient( '_woocommerce_helper_updates_count', 5, HOUR_IN_SECONDS );

		$this->assertNotFalse( get_transient( '_woocommerce_helper_updates_count' ), 'Count transient should be set' );

		// Trigger upgrader complete.
		WC_Helper_Updater::upgrader_process_complete();

		// Verify count transient is cleared.
		$this->assertFalse( get_transient( '_woocommerce_helper_updates_count' ), 'Count transient should be cleared after upgrade' );
	}

	/**
	 * Test should_use_cached_update_data returns false when data is not an array.
	 */
	public function test_should_use_cached_update_data_rejects_non_array() {
		$hash = 'test_hash';

		$this->assertFalse( $this->call_should_use_cached_update_data( 'string', $hash ), 'Should reject string data' );
		$this->assertFalse( $this->call_should_use_cached_update_data( 123, $hash ), 'Should reject numeric data' );
		$this->assertFalse( $this->call_should_use_cached_update_data( null, $hash ), 'Should reject null data' );
		$this->assertFalse( $this->call_should_use_cached_update_data( false, $hash ), 'Should reject false data' );
		$this->assertFalse( $this->call_should_use_cached_update_data( true, $hash ), 'Should reject boolean data' );
	}

	/**
	 * Test should_use_cached_update_data returns false when required keys are missing.
	 */
	public function test_should_use_cached_update_data_rejects_missing_keys() {
		$hash = 'test_hash';

		// Missing both keys.
		$this->assertFalse( $this->call_should_use_cached_update_data( array(), $hash ), 'Should reject empty array' );

		// Missing 'hash' key.
		$data = array( 'products' => array() );
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, $hash ), 'Should reject data without hash key' );

		// Missing 'products' key.
		$data = array( 'hash' => $hash );
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, $hash ), 'Should reject data without products key' );
	}

	/**
	 * Test should_use_cached_update_data returns false when hash is not a string.
	 */
	public function test_should_use_cached_update_data_rejects_non_string_hash() {
		$data = array(
			'hash'     => 123, // Not a string.
			'products' => array(),
		);

		$this->assertFalse( $this->call_should_use_cached_update_data( $data, 'test_hash' ), 'Should reject numeric hash' );

		$data['hash'] = null;
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, 'test_hash' ), 'Should reject null hash' );

		$data['hash'] = array( 'hash' );
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, 'test_hash' ), 'Should reject array hash' );
	}

	/**
	 * Test should_use_cached_update_data returns false when products is not an array.
	 */
	public function test_should_use_cached_update_data_rejects_non_array_products() {
		$hash = 'test_hash';

		$data = array(
			'hash'     => $hash,
			'products' => 'string', // Not an array.
		);
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, $hash ), 'Should reject string products' );

		$data['products'] = 123;
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, $hash ), 'Should reject numeric products' );

		$data['products'] = null;
		$this->assertFalse( $this->call_should_use_cached_update_data( $data, $hash ), 'Should reject null products' );
	}

	/**
	 * Test should_use_cached_update_data returns false when hash doesn't match.
	 */
	public function test_should_use_cached_update_data_rejects_mismatched_hash() {
		$data = array(
			'hash'     => 'cached_hash',
			'products' => array(
				123 => array( 'version' => '1.0.0' ),
			),
		);

		$this->assertFalse(
			$this->call_should_use_cached_update_data( $data, 'different_hash' ),
			'Should reject data with mismatched hash'
		);
	}

	/**
	 * Test should_use_cached_update_data returns true when all validation passes.
	 */
	public function test_should_use_cached_update_data_accepts_valid_data() {
		$hash = 'matching_hash';
		$data = array(
			'hash'     => $hash,
			'products' => array(
				123 => array(
					'version' => '2.0.0',
					'url'     => 'https://woocommerce.com/products/test',
				),
			),
			'updated'  => time(),
			'errors'   => array(),
		);

		$this->assertTrue(
			$this->call_should_use_cached_update_data( $data, $hash ),
			'Should accept valid data with matching hash'
		);
	}

	/**
	 * Test should_use_cached_update_data accepts valid data even with extra keys.
	 */
	public function test_should_use_cached_update_data_accepts_data_with_extra_keys() {
		$hash = 'test_hash';
		$data = array(
			'hash'        => $hash,
			'products'    => array(),
			'updated'     => time(),
			'errors'      => array(),
			'extra_field' => 'extra_value', // Extra key should not cause rejection.
		);

		$this->assertTrue(
			$this->call_should_use_cached_update_data( $data, $hash ),
			'Should accept valid data with extra keys'
		);
	}

	/**
	 * @testdox A forced auto-update flag reaches the plugin update item only when the package can be installed.
	 * @testWith [true, "https://woocommerce.com/package.zip", true]
	 *           [true, "", false]
	 *           [true, "woocommerce-com-expired-123", false]
	 *           [false, "https://woocommerce.com/package.zip", false]
	 *
	 * @param bool   $forced   Whether the update-check response flags the product for a forced auto-update.
	 * @param string $package  Package the update_woo_com_subscription_details filter supplies, as Woo Update Manager does.
	 * @param bool   $expected Whether the update item should carry the autoupdate flag.
	 */
	public function test_transient_update_plugins_forced_autoupdate( bool $forced, string $package, bool $expected ): void {
		$filename = $this->mock_local_woo_plugin();
		$this->mock_update_check_products( 123, $forced );
		$this->mock_update_package( $package );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		$transient = WC_Helper_Updater::transient_update_plugins(
			(object) array(
				'response'  => array(),
				'no_update' => array(),
			)
		);
		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		$item = (array) $transient->response[ $filename ];

		$this->assertSame( $expected, ! empty( $item['autoupdate'] ), 'The plugin update item carries the wrong forced auto-update state' );
	}

	/**
	 * @testdox The update-check request reports the installed version of each local Woo extension.
	 */
	public function test_update_check_request_includes_installed_version(): void {
		$this->mock_local_woo_plugin();

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		WC_Helper_Updater::get_update_data();
		remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );

		$this->assertSame(
			array(
				'product_id' => 123,
				'file_id'    => 'abc123',
				'version'    => '1.0.0',
			),
			$this->mocked_request_products[123],
			'The requested product should report the version installed on the site'
		);
	}

	/**
	 * @testdox The installed version is left out of the update-check cache key.
	 */
	public function test_update_check_cache_key_ignores_installed_version(): void {
		$cached_data = array(
			'hash'     => md5(
				wp_json_encode(
					array(
						123 => array(
							'product_id' => 123,
							'file_id'    => 'abc123',
						),
					)
				)
			),
			'updated'  => time(),
			'products' => array(
				123 => array( 'version' => '2.0.0' ),
			),
			'errors'   => array(),
		);

		set_transient( '_woocommerce_helper_updates', $cached_data, HOUR_IN_SECONDS );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			$result = $this->call_update_check(
				array(
					123 => array(
						'product_id' => 123,
						'file_id'    => 'abc123',
						'version'    => '1.5.0',
					),
				)
			);
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$this->assertSame( $cached_data['products'], $result, 'A payload differing only by installed version should still hit the cache' );
		$this->assertNull( $this->mocked_request_products, 'Updating an extension should not force a remote update-check' );
	}

	/**
	 * @testdox A theme update item reports the PHP requirement the update-check response carries.
	 * @testWith ["8.2"]
	 *           [null]
	 *
	 * @param string|null $requires_php PHP requirement reported for the product, or null when it reports none.
	 */
	public function test_transient_update_themes_passes_requires_php( ?string $requires_php ): void {
		$stylesheet = $this->mock_local_woo_theme();

		$product = array(
			'version'        => '2.0.0',
			'url'            => 'https://woocommerce.com/products/test-theme',
			'package'        => '',
			'slug'           => 'woo-test-theme',
			'upgrade_notice' => '',
		);

		if ( null !== $requires_php ) {
			$product['requires_php'] = $requires_php;
		}

		$this->mocked_updates = array( 456 => $product );

		set_transient(
			'_woocommerce_helper_subscriptions',
			array(
				array(
					'product_id'  => 456,
					'connections' => array(),
				),
			),
			HOUR_IN_SECONDS
		);

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			$transient = WC_Helper_Updater::transient_update_themes(
				(object) array(
					'response' => array(),
					'checked'  => array(),
				)
			);
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$this->assertArrayHasKey( $stylesheet, $transient->response, 'The theme should have been offered an update' );

		$this->assertSame(
			$requires_php,
			$transient->response[ $stylesheet ]['requires_php'] ?? null,
			'The theme update item should report the PHP requirement, so WP_Automatic_Updater::should_update() can enforce it'
		);
	}

	/**
	 * Makes WC_Helper::get_local_woo_plugins() report a single Woo plugin, without touching the filesystem.
	 *
	 * @return string The plugin file name.
	 */
	private function mock_local_woo_plugin(): string {
		$filename = 'woo-test-plugin/woo-test-plugin.php';

		wp_cache_set(
			'plugins',
			array(
				'' => array(
					$filename => array(
						'Name'    => 'Woo Test Plugin',
						'Version' => '1.0.0',
						'Woo'     => '123:abc123',
					),
				),
			),
			'plugins'
		);

		return $filename;
	}

	/**
	 * Makes WC_Helper::get_local_woo_themes() report a single Woo theme, from a fixture theme root.
	 *
	 * @return string The theme stylesheet.
	 */
	private function mock_local_woo_theme(): string {
		$stylesheet       = 'woo-test-theme';
		$this->theme_root = untrailingslashit( get_temp_dir() ) . '/wc-helper-updater-themes';

		wp_mkdir_p( $this->theme_root . '/' . $stylesheet );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp directory.
		file_put_contents(
			$this->theme_root . '/' . $stylesheet . '/style.css',
			"/*\nTheme Name: Woo Test Theme\nVersion: 1.0.0\nWoo: 456:def456\n*/\n"
		);

		// WP_Theme reports a theme without an index.php as broken, and wp_get_themes() then skips it.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp directory.
		file_put_contents( $this->theme_root . '/' . $stylesheet . '/index.php', "<?php\n" );

		register_theme_directory( $this->theme_root );
		wp_clean_themes_cache();

		return $stylesheet;
	}

	/**
	 * Sets the products the mocked update-check response returns.
	 *
	 * @param int  $product_id The Woo product id to return an update for.
	 * @param bool $forced     Whether the product is flagged for a forced auto-update.
	 */
	private function mock_update_check_products( int $product_id, bool $forced ): void {
		$product = array(
			'version'        => '2.0.0',
			'url'            => 'https://woocommerce.com/products/test',
			'package'        => '',
			'slug'           => 'test-plugin',
			'upgrade_notice' => '',
		);

		if ( $forced ) {
			$product['autoupdate'] = true;
		}

		$this->mocked_updates = array( $product_id => $product );
	}

	/**
	 * Sets the package on the update item, the way the Woo Update Manager plugin does.
	 *
	 * @param string $package The package to set.
	 */
	private function mock_update_package( string $package ): void {
		add_filter(
			'update_woo_com_subscription_details',
			function ( $item ) use ( $package ) {
				$item['package'] = $package;

				return $item;
			}
		);
	}

	/**
	 * Mock WC_Helper_API response for testing.
	 *
	 * @param false|array|WP_Error $preempt A preemptive return value of an HTTP request.
	 * @param array                $args HTTP request arguments.
	 * @param string               $url The request URL.
	 * @return array Mocked response.
	 */
	public function mock_helper_api_response( $preempt, $args, $url ) {
		// Only mock WooCommerce.com API calls.
		if ( strpos( $url, 'woocommerce.com' ) === false && strpos( $url, 'api.woocommerce.com' ) === false ) {
			return $preempt;
		}

		$request_body                  = json_decode( $args['body'] ?? '', true );
		$this->mocked_request_products = $request_body['products'] ?? null;

		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => wp_json_encode( $this->mocked_updates ),
		);
	}
}
