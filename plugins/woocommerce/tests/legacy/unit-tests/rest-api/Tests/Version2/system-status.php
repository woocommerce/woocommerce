<?php
/**
 * Class WC_Tests_REST_System_Status file.
 *
 * @package Automattic/WooCommerce/Tests
 */

/**
 * System Status REST Tests.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0
 */
class WC_Tests_REST_System_Status_V2 extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_System_Status_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/system_status', $routes );
		$this->assertArrayHasKey( '/wc/v2/system_status/tools', $routes );
		$this->assertArrayHasKey( '/wc/v2/system_status/tools/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test to make sure system status cannot be accessed without valid creds
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure root properties are present.
	 * (environment, theme, database, etc).
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_returns_root_properties() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'environment', $data );
		$this->assertArrayHasKey( 'database', $data );
		$this->assertArrayHasKey( 'active_plugins', $data );
		$this->assertArrayHasKey( 'theme', $data );
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertArrayHasKey( 'security', $data );
		$this->assertArrayHasKey( 'pages', $data );
	}

	/**
	 * Test to make sure environment response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_environment() {
		wp_set_current_user( $this->user );
		$response    = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data        = $response->get_data();
		$environment = (array) $data['environment'];

		// Make sure all expected data is present.
		$this->assertEquals( 32, count( $environment ) );

		// Test some responses to make sure they match up.
		$this->assertEquals( get_option( 'home' ), $environment['home_url'] );
		$this->assertEquals( get_option( 'siteurl' ), $environment['site_url'] );
		$this->assertEquals( WC()->version, $environment['version'] );
	}

	/**
	 * Test to make sure database response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_database() {
		global $wpdb;
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();
		$database = (array) $data['database'];

		$this->assertEquals( get_option( 'woocommerce_db_version' ), $database['wc_database_version'] );
		$this->assertEquals( $wpdb->prefix, $database['database_prefix'] );
		$this->assertArrayHasKey( 'woocommerce', $database['database_tables'], print_r( $database, true ) );
		$this->assertArrayHasKey( $wpdb->prefix . 'woocommerce_payment_tokens', $database['database_tables']['woocommerce'], print_r( $database, true ) );
	}

	/**
	 * Test to make sure active plugins response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_active_plugins() {
		wp_set_current_user( $this->user );

		$actual_plugins = array( 'hello.php' );
		update_option( 'active_plugins', $actual_plugins );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		update_option( 'active_plugins', array() );

		$data    = $response->get_data();
		$plugins = (array) $data['active_plugins'];

		$this->assertEquals( 1, count( $plugins ) );
		$this->assertEquals( 'Hello Dolly', $plugins[0]['name'] );
	}

	/**
	 * Test to make sure theme response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_theme() {
		wp_set_current_user( $this->user );
		$active_theme = wp_get_theme();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();
		$theme    = (array) $data['theme'];

		$this->assertEquals( 13, count( $theme ) );
		$this->assertEquals( $active_theme->Name, $theme['name'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
	}

	/**
	 * Test to make sure settings response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_settings() {
		wp_set_current_user( $this->user );

		$term_response = array();
		$terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$term_response[ $term->slug ] = strtolower( $term->name );
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();
		$settings = (array) $data['settings'];

		$this->assertEquals( 12, count( $settings ) );
		$this->assertEquals( ( 'yes' === get_option( 'woocommerce_api_enabled' ) ), $settings['api_enabled'] );
		$this->assertEquals( get_woocommerce_currency(), $settings['currency'] );
		$this->assertEquals( $term_response, $settings['taxonomies'] );
	}

	/**
	 * Test to make sure security response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_security() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();
		$settings = (array) $data['security'];

		$this->assertEquals( 2, count( $settings ) );
		$this->assertEquals( 'https' === substr( wc_get_page_permalink( 'shop' ), 0, 5 ), $settings['secure_connection'] );
		$this->assertEquals( ! ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG && WP_DEBUG_DISPLAY ) || 0 === intval( ini_get( 'display_errors' ) ), $settings['hide_errors'] );
	}

	/**
	 * Test to make sure pages response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_info_pages() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
		$data     = $response->get_data();
		$pages    = $data['pages'];
		$this->assertEquals( 5, count( $pages ) );
	}

	/**
	 * Test system status schema.
	 *
	 * @since 3.0.0
	 */
	public function test_system_status_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/system_status' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 10, count( $properties ) );
		$this->assertArrayHasKey( 'environment', $properties );
		$this->assertArrayHasKey( 'database', $properties );
		$this->assertArrayHasKey( 'active_plugins', $properties );
		$this->assertArrayHasKey( 'theme', $properties );
		$this->assertArrayHasKey( 'settings', $properties );
		$this->assertArrayHasKey( 'security', $properties );
		$this->assertArrayHasKey( 'pages', $properties );
	}

	/**
	 * Test to make sure get_items (all tools) response is correct.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_tools() {
		wp_set_current_user( $this->user );

		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status/tools' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $raw_tools ), count( $data ) );
		$this->assertContains(
			array(
				'id'          => 'regenerate_thumbnails',
				'name'        => 'Regenerate shop thumbnails',
				'action'      => 'Regenerate',
				'description' => 'This will regenerate all shop thumbnails to match your theme and/or image settings.',
				'_links'      => array(
					'item' => array(
						array(
							'href'       => rest_url( '/wc/v2/system_status/tools/regenerate_thumbnails' ),
							'embeddable' => true,
						),
					),
				),
			),
			$data
		);
	}

	/**
	 * Test to make sure system status tools cannot be accessed without valid creds
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_tools_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status/tools' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure we can load a single tool correctly.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_tool() {
		wp_set_current_user( $this->user );

		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();
		$raw_tool         = $raw_tools['recount_terms'];

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status/tools/recount_terms' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertEquals( 'Term counts', $data['name'] );
		$this->assertEquals( 'Recount terms', $data['action'] );
		$this->assertEquals( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', $data['description'] );
	}

	/**
	 * Test to make sure a single system status toolscannot be accessed without valid creds.
	 *
	 * @since 3.0.0
	 */
	public function test_get_system_status_tool_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status/tools/recount_terms' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure we can RUN a tool correctly.
	 *
	 * @since 3.0.0
	 */
	public function test_execute_system_tool() {
		wp_set_current_user( $this->user );

		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();
		$raw_tool         = $raw_tools['recount_terms'];

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v2/system_status/tools/recount_terms' ) );
		$data     = $response->get_data();

		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertEquals( 'Term counts', $data['name'] );
		$this->assertEquals( 'Recount terms', $data['action'] );
		$this->assertEquals( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', $data['description'] );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( 1, did_action( 'woocommerce_rest_insert_system_status_tool' ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v2/system_status/tools/not_a_real_tool' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test to make sure a tool cannot be run without valid creds.
	 *
	 * @since 3.0.0
	 */
	public function test_execute_system_status_tool_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v2/system_status/tools/recount_terms' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test system status schema.
	 *
	 * @since 3.0.0
	 */
	public function test_system_status_tool_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/system_status/tools' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'action', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'success', $properties );
		$this->assertArrayHasKey( 'message', $properties );
	}
}
