<?php
/**
 * Plugins REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\Plugins;
use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * WC Tests API Plugins
 */
class WC_Admin_Tests_API_Plugins extends WC_REST_Unit_Test_Case {

	/**
	 * Test plugin slug.
	 */
	private const TEST_PLUGIN_SLUG = 'sample-woo-plugin';

	/**
	 * Test plugin file.
	 */
	private const TEST_PLUGIN_FILE = 'sample-woo-plugin/sample-woo-plugin.php';

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/plugins';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->delete_test_plugin();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Cleanup after each test.
	 */
	public function tearDown(): void {
		deactivate_plugins( 'akismet/akismet.php', true );
		$this->delete_test_plugin();

		parent::tearDown();
	}

	/**
	 * Delete files installed by the plugin installation test.
	 */
	private function delete_test_plugin(): void {
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . self::TEST_PLUGIN_FILE ) ) {
			return;
		}

		delete_plugins( array( self::TEST_PLUGIN_FILE ) );
		wp_clean_plugins_cache( true );
	}

	/**
	 * Test that installation without permission is unauthorized.
	 */
	public function test_install_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', $this->endpoint . '/install' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that installing a valid plugin works.
	 */
	public function test_install_plugin() {
		wp_set_current_user( $this->user );

		$plugin_package = wp_tempnam( 'sample-woo-plugin.zip' );
		copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/sample-woo-plugin.zip', $plugin_package );

		$api_filter = static function ( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || self::TEST_PLUGIN_SLUG !== $args->slug ) {
				return $result;
			}

			return (object) array(
				'slug'          => self::TEST_PLUGIN_SLUG,
				'version'       => '1.0.0',
				'download_link' => 'https://example.com/sample-woo-plugin.zip',
			);
		};

		$download_filter = static function ( $reply, $package, $upgrader ) use ( $plugin_package ) {
			if ( $upgrader instanceof Plugin_Upgrader ) {
				return $plugin_package;
			}

			return $reply;
		};

		$upgrader_hooks = array(
			array( array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20, 1 ),
			array( 'wp_version_check', 10, 0 ),
			array( 'wp_update_plugins', 10, 0 ),
			array( 'wp_update_themes', 10, 0 ),
		);

		$removed_upgrader_hooks = array();

		add_filter( 'plugins_api', $api_filter, 10, 3 );
		add_filter( 'upgrader_pre_download', $download_filter, 10, 3 );
		foreach ( $upgrader_hooks as $upgrader_hook ) {
			if ( false !== has_action( 'upgrader_process_complete', $upgrader_hook[0] ) ) {
				remove_action( 'upgrader_process_complete', $upgrader_hook[0], $upgrader_hook[1] );
				$removed_upgrader_hooks[] = $upgrader_hook;
			}
		}

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'plugins' => self::TEST_PLUGIN_SLUG,
			)
		);

		$this->assertFalse( PluginsHelper::is_plugin_installed( self::TEST_PLUGIN_SLUG ) );

		try {
			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();
			$plugins  = get_plugins();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertEquals( array( self::TEST_PLUGIN_SLUG ), $data['data']['installed'] );
			$this->assertEquals( true, $data['success'] );
			$this->assertArrayHasKey( self::TEST_PLUGIN_FILE, $plugins );
		} finally {
			remove_filter( 'plugins_api', $api_filter, 10 );
			remove_filter( 'upgrader_pre_download', $download_filter, 10 );
			foreach ( $removed_upgrader_hooks as $upgrader_hook ) {
				add_action( 'upgrader_process_complete', $upgrader_hook[0], $upgrader_hook[1], $upgrader_hook[2] );
			}
			if ( file_exists( $plugin_package ) ) {
				wp_delete_file( $plugin_package );
			}
		}
	}

	/**
	 * Test that scheduling a plugin install works.
	 */
	public function test_install_plugin_async() {
		wp_set_current_user( $this->user );
		as_unschedule_all_actions( 'woocommerce_plugins_install_callback' );

		try {
			$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
			$request->set_query_params(
				array(
					'async'   => true,
					'plugins' => self::TEST_PLUGIN_SLUG,
				)
			);
			$response = $this->server->dispatch( $request );

			$data = $response->get_data();

			$this->assertEquals( 200, $response->get_status() );
			$this->assertNotEmpty( $data['data']['job_id'] );
			$this->assertTrue(
				as_has_scheduled_action(
					'woocommerce_plugins_install_callback',
					array( array( self::TEST_PLUGIN_SLUG ) )
				)
			);
		} finally {
			as_unschedule_all_actions( 'woocommerce_plugins_install_callback' );
		}
	}

	/**
	 * Test that installing with invalid params fails.
	 */
	public function test_install_invalid_plugins_param() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_plugins', $data['code'] );
	}

	/**
	 * Test that activating a valid plugin works.
	 */
	public function test_activate_plugin() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'plugins' => 'akismet',
			)
		);
		$response       = $this->server->dispatch( $request );
		$data           = $response->get_data();
		$active_plugins = Plugins::get_active_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( 'akismet', $data['data']['activated'] );
		$this->assertEquals( true, $data['success'] );
		$this->assertContains( 'akismet', $active_plugins );
	}

	/**
	 * Test that scheduling a plugin activation works.
	 */
	public function test_activate_plugin_async() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'async'   => true,
				'plugins' => 'akismet',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$plugins  = get_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'job_id', $data['data'] );
	}

	/**
	 * Test that activating with invalid params fails.
	 */
	public function test_activate_invalid_plugins_param() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_plugins', $data['code'] );
	}

	/**
	 * @return string locale
	 */
	public function set_france_locale() {
		return 'fr_FR';
	}
}
