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
		try {
			// The fixture theme lives on disk and in a global, neither of which the parent teardown resets.
			$this->cleanup_theme_fixture();
			$this->cleanup_plugins_screen();
		} finally {
			parent::tearDown();
		}
	}
		}
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
	 * Plugin list entry for a WooCommerce.com hosted plugin.
	 *
	 * @var array
	 */
	private $woo_plugin_file = 'test-woo-extension/test-woo-extension.php';

	/**
	 * @testdox Connect notice renders on a Woo plugin row that has no pending update.
	 */
	public function test_connect_notice_renders_without_pending_update(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );

		$output = $this->render_connect_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'woo-connect-notice', $output, 'The notice row should be rendered.' );
		$this->assertStringContainsString( 'woocommerce-connect-your-store', $output, 'The notice should link to the connect page.' );
		$this->assertStringContainsString( '>Connect your store</a> for security updates, product improvements, and support.', $output );
	}

	/**
	 * @testdox Connect notice is skipped when Core already renders an update row for the plugin.
	 */
	public function test_connect_notice_is_skipped_when_core_renders_an_update_row(): void {
		$this->prepare_plugins_screen();

		$transient                                     = new stdClass();
		$transient->response                           = array();
		$transient->response[ $this->woo_plugin_file ] = (object) array(
			'id'          => 'woocommerce-com-123',
			'new_version' => '2.0.0',
			'package'     => '',
		);
		set_site_transient( 'update_plugins', $transient );

		$output = $this->render_connect_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertSame( '', $output, 'Core already carries the connect prompt on its update row.' );
	}

	/**
	 * @testdox Connect notice is skipped for plugins that are not hosted on WooCommerce.com.
	 */
	public function test_connect_notice_is_skipped_for_non_woo_plugins(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );

		$output = $this->render_connect_notice(
			'some-other-plugin/some-other-plugin.php',
			array(
				'Name'    => 'Some Other Plugin',
				'Version' => '1.0.0',
				'Woo'     => '',
			)
		);

		$this->assertSame( '', $output, 'Only WooCommerce.com hosted plugins should get the notice.' );
	}

	/**
	 * @testdox Connect notice is skipped for users who cannot update plugins.
	 */
	public function test_connect_notice_is_skipped_without_the_update_plugins_capability(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$output = $this->render_connect_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertSame( '', $output, 'Core renders no update row for these users either.' );
	}

	/**
	 * @testdox Connect notice is skipped on the Woo Update Manager row.
	 */
	public function test_connect_notice_is_skipped_for_the_woo_update_manager(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );

		$output = $this->render_connect_notice(
			WC_Woo_Update_Manager_Plugin::WOO_UPDATE_MANAGER_PLUGIN_MAIN_FILE,
			array(
				'Name'    => 'WooCommerce.com Update Manager',
				'Version' => '1.0.3',
				'Woo'     => '18734003407318:abcdef',
			)
		);

		$this->assertSame( '', $output, 'The Update Manager delivers these updates, so it gets no prompt of its own.' );
	}

	/**
	 * @testdox Purchase notice links to the product page when the connected account holds no subscription.
	 */
	public function test_purchase_notice_renders_without_a_subscription(): void {
		$this->prepare_plugins_screen();
		$this->set_subscriptions( array() );

		$transient                                      = new stdClass();
		$transient->response                            = array();
		$transient->no_update                           = array();
		$transient->no_update[ $this->woo_plugin_file ] = (object) array(
			'id'  => 'woocommerce-com-123',
			'url' => 'https://woocommerce.com/products/test-woo-extension/',
		);
		set_site_transient( 'update_plugins', $transient );

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'woo-subscription-notice', $output, 'The notice row should be rendered.' );
		$this->assertStringContainsString( 'products/test-woo-extension', $output, 'The link should point at the product page.' );
		$this->assertStringContainsString( 'utm_campaign=pu_plugin_row_purchase', $output, 'The link should carry the campaign parameters.' );
		$this->assertStringContainsString( 'woocommerce-purchase-subscription', $output, 'The link should carry the tracked class.' );
		$this->assertStringNotContainsString( 'target=', $output, 'Links in the notices stay in the admin tab.' );
		$this->assertStringContainsString( '>Subscribe</a> now for security updates, product improvements, and support.', $output );
	}

	/**
	 * @testdox Purchase notice falls back to the cart when the update data carries no product URL.
	 */
	public function test_purchase_notice_falls_back_to_the_cart_without_a_product_url(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions( array() );

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'add-to-cart=123', $output, 'The link should add the product to the WooCommerce.com cart.' );
	}

	/**
	 * @testdox Subscription notice is skipped for a subscription that needs no action.
	 */
	public function test_subscription_notice_is_skipped_for_a_healthy_subscription(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions( array( $this->subscription() ) );

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertSame( '', $output, 'Nothing to say about a current subscription.' );
	}

	/**
	 * @testdox Renewal notice renders when the subscription has expired.
	 */
	public function test_subscription_notice_renders_renewal_for_an_expired_subscription(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions(
			array(
				$this->subscription(
					array(
						'expired'               => true,
						'product_regular_price' => '&#36;59',
					)
				),
			)
		);

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'Your subscription for this extension has expired.', $output );
		$this->assertStringContainsString( '>Renew your subscription</a> for security updates, product improvements, and support.', $output );
		$this->assertStringNotContainsString( '&#036;59', $output, 'The price is no longer part of the message.' );
		$this->assertStringContainsString( 'renew_product=123', $output, 'Renewal should renew the subscription, not buy a new one.' );
		$this->assertStringContainsString( 'product_key=key', $output, 'The link should name the subscription being renewed.' );
		$this->assertStringContainsString( 'order_id=456', $output, 'The link should name the order being renewed.' );
		$this->assertStringNotContainsString( 'add-to-cart', $output );
		$this->assertStringContainsString( 'woocommerce-renew-subscription', $output, 'The link should carry the tracked class.' );
		$this->assertStringNotContainsString( 'target=', $output, 'Links in the notices stay in the admin tab.' );
	}

	/**
	 * @testdox Renewal falls back to adding the product to the cart when the record cannot name the subscription.
	 *
	 * @dataProvider provider_records_missing_a_renewal_identifier
	 *
	 * @param array $overrides Fields to change on the expired subscription record.
	 */
	public function test_subscription_notice_falls_back_to_the_cart_without_renewal_identifiers( array $overrides ): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions( array( $this->subscription( array_merge( array( 'expired' => true ), $overrides ) ) ) );

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'Your subscription for this extension has expired.', $output, 'The merchant still learns the subscription expired.' );
		$this->assertStringContainsString( 'add-to-cart=123', $output, 'A link that buys the product beats one that renews nothing.' );
		$this->assertStringNotContainsString( 'renew_product', $output );
		$this->assertStringContainsString( 'utm_campaign=pu_plugin_row_renew', $output );
	}

	/**
	 * @testdox Renewal accepts an order ID sent as a digit string.
	 */
	public function test_subscription_notice_renews_with_a_digit_string_order_id(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions(
			array(
				$this->subscription(
					array(
						'expired'  => true,
						'order_id' => '456',
					)
				),
			)
		);

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'renew_product=123', $output );
		$this->assertStringContainsString( 'order_id=456', $output );
	}

	/**
	 * Expired records that cannot be renewed by reference.
	 *
	 * @return array[]
	 */
	public function provider_records_missing_a_renewal_identifier(): array {
		return array(
			'no product key'         => array( array( 'product_key' => '' ) ),
			'product key is false'   => array( array( 'product_key' => false ) ),
			'product key is a list'  => array( array( 'product_key' => array( 'key' ) ) ),
			'no order ID'            => array( array( 'order_id' => '' ) ),
			'order ID absent'        => array( array( 'order_id' => null ) ),
			'order ID is zero'       => array( array( 'order_id' => 0 ) ),
			'order ID is false'      => array( array( 'order_id' => false ) ),
			'order ID is a list'     => array( array( 'order_id' => array( 456 ) ) ),
			'order ID is not digits' => array( array( 'order_id' => '45a6' ) ),
		);
	}

	/**
	 * @testdox The update-row message renews the exact expired subscription too.
	 */
	public function test_update_row_notice_renews_the_expired_subscription(): void {
		$this->prepare_plugins_screen();
		$this->set_subscriptions( array( $this->subscription( array( 'expired' => true ) ) ) );

		ob_start();
		WC_Helper_Updater::display_notice_for_expired_and_expiring_subscriptions(
			$this->woo_plugin_data(),
			(object) array( 'id' => 'woocommerce-com-123' )
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'renew_product=123', $output );
		$this->assertStringContainsString( 'product_key=key', $output );
		$this->assertStringContainsString( 'order_id=456', $output );
		$this->assertStringContainsString( 'utm_campaign=pu_plugin_screen_renew', $output );
		$this->assertStringNotContainsString( 'add-to-cart', $output );
	}

	/**
	 * @testdox Auto-renew notice renders for a subscription lapsing without auto-renew.
	 */
	public function test_subscription_notice_renders_autorenew_for_an_expiring_subscription(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions(
			array(
				$this->subscription(
					array(
						'expiring'  => true,
						'autorenew' => false,
					)
				),
			)
		);

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertStringContainsString( 'woocommerce-enable-autorenew', $output, 'The link should point at auto-renew.' );
		$this->assertStringContainsString( 'my-subscriptions', $output, 'Auto-renew is enabled from the My Subscriptions page.' );
	}

	/**
	 * @testdox Auto-renew notice is skipped when the lapsing subscription already auto-renews.
	 */
	public function test_subscription_notice_is_skipped_when_autorenew_is_on(): void {
		$this->prepare_plugins_screen();
		delete_site_transient( 'update_plugins' );
		$this->set_subscriptions(
			array(
				$this->subscription(
					array(
						'expiring'  => true,
						'autorenew' => true,
					)
				),
			)
		);

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertSame( '', $output, 'Auto-renew already covers this subscription.' );
	}

	/**
	 * A subscription record as the WooCommerce.com API returns it.
	 *
	 * @param array $overrides Fields to override on the default record.
	 * @return array
	 */
	private function subscription( array $overrides = array() ): array {
		return array_merge(
			array(
				'product_id'  => 123,
				'product_key' => 'key',
				'order_id'    => 456,
				'expired'     => false,
				'expiring'    => false,
				'lifetime'    => false,
				'autorenew'   => true,
				'expires'     => time() + DAY_IN_SECONDS,
				'connections' => array(),
			),
			$overrides
		);
	}

	/**
	 * Stand in a subscriptions payload, which WC_Helper reads from its transient.
	 *
	 * @param array $subscriptions Subscription records.
	 * @return void
	 */
	private function set_subscriptions( array $subscriptions ): void {
		WC_Helper_Options::update( 'auth', array( 'site_id' => 45 ) );
		set_transient( '_woocommerce_helper_subscriptions', $subscriptions, HOUR_IN_SECONDS );
	}

	/**
	 * @testdox Subscription notice is skipped when Core already renders an update row for the plugin.
	 */
	public function test_subscription_notice_is_skipped_when_core_renders_an_update_row(): void {
		$this->prepare_plugins_screen();
		$this->set_subscriptions( array() );

		$transient                                     = new stdClass();
		$transient->response                           = array();
		$transient->response[ $this->woo_plugin_file ] = (object) array(
			'id'          => 'woocommerce-com-123',
			'new_version' => '2.0.0',
			'package'     => '',
		);
		set_site_transient( 'update_plugins', $transient );

		$output = $this->render_subscription_notice( $this->woo_plugin_file, $this->woo_plugin_data() );

		$this->assertSame( '', $output, 'display_notice_for_plugins_without_subscription() covers that row.' );
	}

	/**
	 * @testdox Update-row notices ignore a response whose ID is not one WooCommerce wrote.
	 *
	 * @dataProvider provider_foreign_update_response_ids
	 *
	 * @param mixed $id The response ID, or null for a response without one.
	 */
	public function test_update_row_notices_ignore_foreign_response_ids( $id ): void {
		$this->prepare_plugins_screen();
		$this->set_subscriptions( array( $this->subscription( array( 'expired' => true ) ) ) );

		$response = is_null( $id ) ? new stdClass() : (object) array( 'id' => $id );

		ob_start();
		WC_Helper_Updater::display_notice_for_expired_and_expiring_subscriptions( $this->woo_plugin_data(), $response );
		WC_Helper_Updater::display_notice_for_plugins_without_subscription( $this->woo_plugin_data(), $response );
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'Digits must not be scraped out of an ID that is not ours.' );
	}

	/**
	 * Response IDs that must not resolve to product 123.
	 *
	 * @return array[]
	 */
	public function provider_foreign_update_response_ids(): array {
		return array(
			'missing'                => array( null ),
			'empty'                  => array( '' ),
			'not a string'           => array( 123 ),
			'WordPress.org plugin'   => array( 'w.org/plugins/some-plugin' ),
			'digits in the wrong id' => array( 'woocommerce-com-12foo3' ),
			'prefix only'            => array( 'woocommerce-com-' ),
			'trailing characters'    => array( 'woocommerce-com-123-beta' ),
		);
	}

	/**
	 * @testdox The no-subscription update-row notice renders for an ID WooCommerce wrote.
	 */
	public function test_update_row_notice_renders_for_a_product_without_a_subscription(): void {
		$this->prepare_plugins_screen();
		$this->set_subscriptions( array() );

		ob_start();
		WC_Helper_Updater::display_notice_for_plugins_without_subscription(
			$this->woo_plugin_data(),
			(object) array( 'id' => 'woocommerce-com-123' )
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'add-to-cart=123', $output );
		$this->assertStringContainsString( 'woocommerce-purchase-subscription', $output );
	}

	/**
	 * Capture what the subscription notice callback prints for a plugin row.
	 *
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin metadata.
	 * @return string
	 */
	private function render_subscription_notice( string $plugin_file, array $plugin_data ): string {
		ob_start();
		WC_Helper_Updater::display_subscription_notice_for_woo_plugins( $plugin_file, $plugin_data );

		return ob_get_clean();
	}

	/**
	 * Plugin metadata for the WooCommerce.com hosted plugin used by these tests.
	 *
	 * @return array
	 */
	private function woo_plugin_data(): array {
		return array(
			'Name'    => 'Test Woo Extension',
			'Version' => '1.0.0',
			'Woo'     => '123:abcdef',
		);
	}

	/**
	 * Set up the plugins screen state that the after_plugin_row callback reads from.
	 *
	 * get_plugins() returns whatever is in the 'plugins' cache group, which lets these tests
	 * stand in a plugin list without touching the filesystem.
	 *
	 * @return void
	 */
	private function prepare_plugins_screen(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$admin    = new WP_User( $admin_id );
		$admin->add_cap( 'update_plugins' );
		wp_set_current_user( $admin_id );

		wp_cache_set(
			'plugins',
			array(
				'' => array(
					$this->woo_plugin_file => $this->woo_plugin_data(),
					'some-other-plugin/some-other-plugin.php' => array(
						'Name'    => 'Some Other Plugin',
						'Version' => '1.0.0',
						'Woo'     => '',
					),
				),
			),
			'plugins'
		);

		$list_table = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'get_column_count' ) )
			->getMock();
		$list_table->method( 'get_column_count' )->willReturn( 4 );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Stands in for the plugins screen list table; cleanup_plugins_screen() unsets it.
		$GLOBALS['wp_list_table'] = $list_table;
	}

	/**
	 * Capture what the after_plugin_row callback prints for a plugin row.
	 *
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin metadata.
	 * @return string
	 */
	private function render_connect_notice( string $plugin_file, array $plugin_data ): string {
		ob_start();
		WC_Helper_Updater::display_connect_notice_for_woo_plugins( $plugin_file, $plugin_data );

		return ob_get_clean();
	}

	/**
	 * Clean up the plugins screen state set up by prepare_plugins_screen().
	 *
	 * @return void
	 */
	private function cleanup_plugins_screen(): void {
		WC_Helper_Options::update( 'auth', array() );
		delete_transient( '_woocommerce_helper_subscriptions' );
		wp_cache_delete( 'plugins', 'plugins' );
		delete_site_transient( 'update_plugins' );
		unset( $GLOBALS['wp_list_table'] );
		wp_set_current_user( 0 );
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
	 * @testdox Only an autoupdate value that reads as boolean true forces an update.
	 *
	 * @dataProvider autoupdate_flag_values
	 *
	 * @param mixed $flag     Raw value the update-check response reports for autoupdate.
	 * @param bool  $expected Whether the update item should carry the autoupdate flag.
	 */
	public function test_transient_update_plugins_validates_autoupdate_flag( $flag, bool $expected ): void {
		$filename = $this->mock_local_woo_plugin();
		$this->mock_update_check_autoupdate_flag( 123, $flag );
		$this->mock_update_package( 'https://woocommerce.com/package.zip' );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			$transient = WC_Helper_Updater::transient_update_plugins(
				(object) array(
					'response'  => array(),
					'no_update' => array(),
				)
			);
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$item = (array) $transient->response[ $filename ];

		$this->assertSame( $expected, ! empty( $item['autoupdate'] ), 'A non-boolean autoupdate value was read as the wrong forced auto-update state' );
	}

	/**
	 * Values the update-check response could report for autoupdate, and whether each forces an update.
	 *
	 * Shapes are what survives a JSON round trip, since the response is decoded as an array.
	 *
	 * @return array
	 */
	public function autoupdate_flag_values(): array {
		return array(
			'boolean true'   => array( true, true ),
			'string "true"'  => array( 'true', true ),
			'string "1"'     => array( '1', true ),
			'integer 1'      => array( 1, true ),
			'boolean false'  => array( false, false ),
			'string "false"' => array( 'false', false ),
			'string "0"'     => array( '0', false ),
			'integer 0'      => array( 0, false ),
			'empty string'   => array( '', false ),
			'null'           => array( null, false ),
			'empty array'    => array( array(), false ),
			'list'           => array( array( 'yes' ), false ),
			'object'         => array( array( 'forced' => true ), false ),
		);
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
	 * @testdox The downloads data reports an installed theme's file ID and version too.
	 */
	public function test_downloads_data_reports_installed_theme_version(): void {
		$this->mock_local_woo_theme();

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			WC_Helper_Updater::get_available_extensions_downloads_data();
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$this->assertSame(
			array(
				'product_id' => 456,
				'file_id'    => 'def456',
				'version'    => '1.0.0',
			),
			$this->mocked_request_products[456],
			'A theme must be reported the same way here as by get_update_data()'
		);
	}

	/**
	 * @testdox Both update entry points send the same payload, so they share one cached response.
	 */
	public function test_update_entry_points_send_the_same_payload(): void {
		$this->mock_local_woo_plugin();
		$this->mock_local_woo_theme();

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			WC_Helper_Updater::get_update_data();
			$from_update_data = $this->mocked_request_products;

			// Drop the cached response so the second entry point has to ask the server too.
			delete_transient( '_woocommerce_helper_updates' );
			$this->mocked_request_products = null;

			WC_Helper_Updater::get_available_extensions_downloads_data();
			$from_downloads_data = $this->mocked_request_products;
		} finally {
			remove_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ) );
		}

		$this->assertNotNull( $from_downloads_data, 'The second call should have gone to the server' );
		$this->assertSame( $from_update_data, $from_downloads_data );
	}

	/**
	 * @testdox The installed version is part of the update-check cache key.
	 */
	public function test_update_check_cache_key_includes_installed_version(): void {
		// A response cached while 2.0.0 was installed, with the autoupdate decision made for it.
		$cached_data = array(
			'hash'     => md5(
				wp_json_encode(
					array(
						123 => array(
							'product_id' => 123,
							'file_id'    => 'abc123',
							'version'    => '2.0.0',
						),
					)
				)
			),
			'updated'  => time(),
			'products' => array(
				123 => array(
					'version'    => '2.0.0',
					'autoupdate' => false,
				),
			),
			'errors'   => array(),
		);

		set_transient( '_woocommerce_helper_updates', $cached_data, HOUR_IN_SECONDS );

		add_filter( 'pre_http_request', array( $this, 'mock_helper_api_response' ), 10, 3 );
		try {
			// The site has since rolled back to 1.5.0.
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

		$this->assertSame( '1.5.0', $this->mocked_request_products[123]['version'] ?? null, 'A rollback must ask the server again, since the autoupdate flag was decided for the build that was installed at the time' );
		$this->assertNotSame( $cached_data['products'], $result, 'The stale decision must not be served for a different build' );
	}

	/**
	 * @testdox A forced auto-update flag reaches the theme update item only when the package can be installed.
	 * @testWith [true, "https://woocommerce.com/package.zip", true]
	 *           [true, "", false]
	 *           [true, "woocommerce-com-expired-456", false]
	 *           [false, "https://woocommerce.com/package.zip", false]
	 *
	 * @param bool   $forced   Whether the update-check response flags the product for a forced auto-update.
	 * @param string $package  Package the update_woo_com_subscription_details filter supplies, as Woo Update Manager does.
	 * @param bool   $expected Whether the update item should carry the autoupdate flag.
	 */
	public function test_transient_update_themes_forced_autoupdate( bool $forced, string $package, bool $expected ): void {
		$stylesheet = $this->mock_local_woo_theme();
		$this->mock_update_check_products( 456, $forced );
		$this->mock_update_package( $package );

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

		$item = $transient->response[ $stylesheet ];

		$this->assertSame( $expected, ! empty( $item['autoupdate'] ), 'The theme update item carries the wrong forced auto-update state' );
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
	 * Sets the raw value the mocked update-check response reports for autoupdate.
	 *
	 * @param int   $product_id The Woo product id to return an update for.
	 * @param mixed $flag       Raw value reported for autoupdate.
	 */
	private function mock_update_check_autoupdate_flag( int $product_id, $flag ): void {
		$this->mock_update_check_products( $product_id, false );
		$this->mocked_updates[ $product_id ]['autoupdate'] = $flag;
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
