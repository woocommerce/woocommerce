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
 * @since 3.5.0
 */
class WC_Tests_REST_System_Status extends WC_REST_Unit_Test_Case {
	/**
	 * User variable.
	 *
	 * @var int
	 */
	protected static int $administrator_user;

	/**
	 * Post fixture used to make the post-type inventory deterministic.
	 *
	 * @var int
	 */
	protected static int $post_id;

	/**
	 * User variable.
	 *
	 * @var int
	 */
	protected static int $no_user = 0;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$administrator_user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$post_id            = $factory->post->create(
			array(
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Delete the post fixture after the class completes.
	 */
	public static function wpTearDownAfterClass(): void {
		wp_delete_post( self::$post_id, true );
	}

	/**
	 * Setup our test server.
	 */
	public function setUp(): void {
		parent::setUp();

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );
	}

	/**
	 * Fetches the System Status Report data and caches it.
	 * @param  int $user The ID of a WordPress user to switch to before fetching the data.
	 * @return Array An array of the data returned by the System Status Report endpoint.
	 */
	private function fetch_or_get_system_status_data_for_user( int $user ) {
		if ( $user < 0 ) {
			return null;
		}

		static $system_status_data = array();
		if ( ! isset( $system_status_data[ 'user' . $user ] ) ) {
			wp_set_current_user( $user );
			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status' ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertIsArray( $data );
			$system_status_data[ 'user' . $user ] = $data;
		}
		return $system_status_data[ 'user' . $user ];
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/system_status', $routes );
		$this->assertArrayHasKey( '/wc/v3/system_status/tools', $routes );
		$this->assertArrayHasKey( '/wc/v3/system_status/tools/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test to make sure system status cannot be accessed without valid creds
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_without_permission() {
		wp_set_current_user( self::$no_user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure root properties are present.
	 * (environment, theme, database, etc).
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_returns_root_properties() {
		$system_status_data = $this->fetch_or_get_system_status_data_for_user( self::$administrator_user );
		$expected_keys      = array(
			'environment',
			'database',
			'active_plugins',
			'inactive_plugins',
			'dropins_mu_plugins',
			'theme',
			'settings',
			'security',
			'pages',
			'post_type_counts',
			'logging',
		);
		$actual_keys        = array_keys( $system_status_data );

		sort( $expected_keys );
		sort( $actual_keys );

		$this->assertSame( $expected_keys, $actual_keys );
		$this->assertIsArray( $system_status_data['environment'] );
		$this->assertIsBool( $system_status_data['environment']['wp_multisite'] );
		$this->assertIsArray( $system_status_data['database'] );
		$this->assertIsArray( $system_status_data['active_plugins'] );
		$this->assertIsArray( $system_status_data['inactive_plugins'] );
		$this->assertIsArray( $system_status_data['dropins_mu_plugins'] );
		$this->assertIsArray( $system_status_data['theme'] );
		$this->assertIsArray( $system_status_data['settings'] );
		$this->assertIsArray( $system_status_data['security'] );
		$this->assertIsArray( $system_status_data['pages'] );
		$this->assertIsArray( $system_status_data['post_type_counts'] );
		$this->assertNotEmpty( $system_status_data['post_type_counts'] );

		$post_type_count = (array) reset( $system_status_data['post_type_counts'] );
		$this->assertIsString( $post_type_count['type'] );
		$this->assertIsString( $post_type_count['count'] );

		$this->assertIsArray( $system_status_data['logging'] );
		$this->assertIsBool( $system_status_data['logging']['logging_enabled'] );
		$this->assertIsString( $system_status_data['logging']['default_handler'] );
		$this->assertIsInt( $system_status_data['logging']['retention_period_days'] );
		$this->assertIsString( $system_status_data['logging']['level_threshold'] );
		$this->assertIsString( $system_status_data['logging']['log_directory_size'] );
	}

	/**
	 * Test to make sure environment response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_environment() {
		$store_id = get_option( \WC_Install::STORE_ID_OPTION, null );
		if ( empty( $store_id ) ) {
			$store_id = 'a1b2c3d4-e5f6-a1b2-c3d4-a1b2c3d4e5f6';
			update_option( \WC_Install::STORE_ID_OPTION, $store_id );
		}

		$environment = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['environment'];

		// Make sure all expected data is present.
		$this->assertEquals( 35, count( $environment ) );

		// Test some responses to make sure they match up.
		$this->assertEquals( get_option( 'home' ), $environment['home_url'] );
		$this->assertEquals( get_option( 'siteurl' ), $environment['site_url'] );
		$this->assertEquals( get_option( \WC_Install::STORE_ID_OPTION, null ), $environment['store_id'] );
		$this->assertEquals( $store_id, $environment['store_id'] );
		$this->assertEquals( WC()->version, $environment['version'] );
		$this->assertEquals( wp_get_environment_type(), $environment['wp_environment_type'] );
	}

	/**
	 * Test to make sure that it is possible to filter
	 * the environment fields returned in the response.
	 */
	public function test_get_system_status_info_environment_filtered_by_field() {
		if ( ! version_compare( get_bloginfo( 'version' ), '5.3', '>=' ) ) {
			$this->markTestSkipped( 'Skipping because nested property support was introduced in 5.3.' );
			return;
		}
		$expected_data = array(
			'environment' => array(
				'version' => WC()->version,
			),
		);
		wp_set_current_user( self::$administrator_user );

		$request = new WP_REST_Request( 'GET', '/wc/v3/system_status' );
		$request->set_query_params( array( '_fields' => 'environment.version' ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( $expected_data, $data );
	}

	/**
	 * Test to make sure database response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_database() {
		global $wpdb;
		$database = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['database'];

		$this->assertEquals( get_option( 'woocommerce_db_version' ), $database['wc_database_version'] );
		$this->assertEquals( $wpdb->prefix, $database['database_prefix'] );
		$this->assertArrayHasKey( 'woocommerce', $database['database_tables'], wc_print_r( $database, true ) );
		$this->assertArrayHasKey( $wpdb->prefix . 'woocommerce_payment_tokens', $database['database_tables']['woocommerce'], wc_print_r( $database, true ) );
	}

	/**
	 * Test to make sure active plugins response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_active_plugins() {
		wp_set_current_user( self::$administrator_user );
		delete_transient( 'wc_system_status_active_plugins' );

		$actual_plugins = array( 'hello.php' );
		update_option( 'active_plugins', $actual_plugins );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status' ) );
		update_option( 'active_plugins', array() );

		$data    = $response->get_data();
		$plugins = (array) $data['active_plugins'];
		$this->assertEquals( 1, count( $plugins ) );

		$plugin = reset( $plugins );
		$this->assertArrayHasKey( 'plugin', $plugin );
		$this->assertEquals( 'hello.php', $plugin['plugin'] );
		$this->assertArrayHasKey( 'name', $plugin );
		$this->assertEquals( 'Hello Dolly', $plugin['name'] );
		$this->assertArrayHasKey( 'version', $plugin );
		$this->assertArrayHasKey( 'version_latest', $plugin );
		$this->assertArrayHasKey( 'url', $plugin );
		$this->assertArrayHasKey( 'author_name', $plugin );
		$this->assertArrayHasKey( 'author_url', $plugin );
		$this->assertArrayHasKey( 'network_activated', $plugin );
		$this->assertEquals( false, $plugin['network_activated'] );
	}

	/**
	 * Test to make sure theme response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_theme() {
		$active_theme = wp_get_theme();
		$theme        = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['theme'];

		$this->assertEquals( 14, count( $theme ) );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertEquals( $active_theme->Name, $theme['name'] );
	}

	/**
	 * Test to make sure settings response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_settings() {
		$term_response = array();
		$terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$term_response[ $term->slug ] = strtolower( $term->name );
		}

		$settings = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['settings'];

		$this->assertEquals( 17, count( $settings ) );
		$this->assertEquals( WC()->legacy_rest_api_is_available(), $settings['api_enabled'] );
		$this->assertEquals( get_woocommerce_currency(), $settings['currency'] );
		$this->assertEquals( $term_response, $settings['taxonomies'] );
	}

	/**
	 * Test to make sure security response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_security() {
		$settings = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['security'];

		$this->assertEquals( 2, count( $settings ) );
		$this->assertEquals( 'https' === substr( wc_get_page_permalink( 'shop' ), 0, 5 ), $settings['secure_connection'] );
		$this->assertEquals( ! ( defined( 'WP_DEBUG' ) && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG && WP_DEBUG_DISPLAY ) || 0 === intval( ini_get( 'display_errors' ) ), $settings['hide_errors'] );
	}

	/**
	 * Test to make sure pages response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_pages() {
		$pages = $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['pages'];
		$this->assertEquals( 5, count( $pages ) );
	}

	/**
	 * Test system status schema.
	 *
	 * @since 3.5.0
	 */
	public function test_system_status_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/system_status' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$expected_keys = array(
			'environment',
			'database',
			'active_plugins',
			'inactive_plugins',
			'dropins_mu_plugins',
			'theme',
			'settings',
			'security',
			'pages',
			'post_type_counts',
			'logging',
		);
		$actual_keys   = array_keys( $properties );

		sort( $expected_keys );
		sort( $actual_keys );

		$this->assertSame( $expected_keys, $actual_keys );
		$this->assertSame( 'object', $properties['environment']['type'] );
		$this->assertSame( 'boolean', $properties['environment']['properties']['wp_multisite']['type'] );
		$this->assertSame( 'array', $properties['dropins_mu_plugins']['type'] );
		$this->assertSame( 'array', $properties['post_type_counts']['type'] );
		$this->assertSame( 'object', $properties['logging']['type'] );
		$this->assertSame( 'boolean', $properties['logging']['properties']['logging_enabled']['type'] );
		$this->assertSame( 'string', $properties['logging']['properties']['default_handler']['type'] );
		$this->assertSame( 'integer', $properties['logging']['properties']['retention_period_days']['type'] );
		$this->assertSame( 'string', $properties['logging']['properties']['level_threshold']['type'] );
		$this->assertSame( 'string', $properties['logging']['properties']['log_directory_size']['type'] );
	}

	/**
	 * Test to make sure get_items (all tools) response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_tools() {
		wp_set_current_user( self::$administrator_user );
		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status/tools' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( count( $raw_tools ), $data );

		$tools_by_id = array_column( $data, null, 'id' );
		foreach ( array( 'clear_transients', 'clear_expired_transients', 'clear_expired_download_permissions', 'regenerate_product_lookup_tables' ) as $tool_id ) {
			$this->assertArrayHasKey( $tool_id, $tools_by_id );
			$this->assertSame( $tool_id, $tools_by_id[ $tool_id ]['id'] );
			$this->assertIsString( $tools_by_id[ $tool_id ]['name'] );
			$this->assertIsString( $tools_by_id[ $tool_id ]['action'] );
			$this->assertIsString( $tools_by_id[ $tool_id ]['description'] );
			$this->assertSame( rest_url( '/wc/v3/system_status/tools/' . $tool_id ), $tools_by_id[ $tool_id ]['_links']['item'][0]['href'] );
			$this->assertTrue( $tools_by_id[ $tool_id ]['_links']['item'][0]['embeddable'] );
		}

		$query_params = array(
			'_fields' => 'id,name,nonexisting',
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v3/system_status/tools' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( count( $raw_tools ), $data );

		$tools_by_id = array_column( $data, null, 'id' );
		$this->assertArrayHasKey( 'clear_transients', $tools_by_id );
		$this->assertSame( 'clear_transients', $tools_by_id['clear_transients']['id'] );
		$this->assertIsString( $tools_by_id['clear_transients']['name'] );
		foreach ( $data as $item ) {
			// Fields that are not requested are not returned in response.
			$this->assertArrayNotHasKey( 'action', $item );
			$this->assertArrayNotHasKey( 'description', $item );
			// Links are part of data in collections, so excluded if not explicitly requested.
			$this->assertArrayNotHasKey( '_links', $item );
			// Non existing field is ignored.
			$this->assertArrayNotHasKey( 'nonexisting', $item );
		}

		// Links are part of data, not links in collections.
		$links = $response->get_links();
		$this->assertEquals( 0, count( $links ) );
	}

	/**
	 * Test to make sure system status tools cannot be accessed without valid creds
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_tools_without_permission() {
		wp_set_current_user( self::$no_user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status/tools' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure we can load a single tool correctly.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_tool() {
		wp_set_current_user( self::$administrator_user );

		foreach ( array( 'recount_terms', 'clear_transients' ) as $tool_id ) {
			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status/tools/' . $tool_id ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();

			$this->assertSame( $tool_id, $data['id'] );
			$this->assertIsString( $data['name'] );
			$this->assertIsString( $data['action'] );
			$this->assertIsString( $data['description'] );

			$links = $response->get_links();
			$this->assertCount( 1, $links );
			$this->assertSame( rest_url( '/wc/v3/system_status/tools/' . $tool_id ), $links['item'][0]['href'] );
			$this->assertTrue( $links['item'][0]['attributes']['embeddable'] );

			// Test for _fields query parameter.
			$request = new WP_REST_Request( 'GET', '/wc/v3/system_status/tools/' . $tool_id );
			$request->set_query_params(
				array(
					'_fields' => 'id,name,nonexisting',
				)
			);
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();

			$this->assertSame( $tool_id, $data['id'] );
			$this->assertIsString( $data['name'] );
			$this->assertArrayNotHasKey( 'action', $data );
			$this->assertArrayNotHasKey( 'description', $data );
			// Links are part of links, not data in single items.
			$this->assertArrayNotHasKey( '_links', $data );

			// Links are part of links, not data in single item response.
			$links = $response->get_links();
			$this->assertCount( 1, $links );
			$this->assertSame( rest_url( '/wc/v3/system_status/tools/' . $tool_id ), $links['item'][0]['href'] );
			$this->assertTrue( $links['item'][0]['attributes']['embeddable'] );
		}
	}

	/**
	 * Test to make sure a single system status toolscannot be accessed without valid creds.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_tool_without_permission() {
		wp_set_current_user( self::$no_user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status/tools/recount_terms' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test to make sure we can RUN a tool correctly.
	 *
	 * @since 3.5.0
	 */
	public function test_execute_system_tool() {
		wp_set_current_user( self::$administrator_user );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/system_status/tools/recount_terms' ) );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertSame( 'recount_terms', $data['id'] );
		$this->assertIsString( $data['name'] );
		$this->assertIsString( $data['action'] );
		$this->assertIsString( $data['description'] );
		$this->assertTrue( $data['success'] );
		$this->assertIsString( $data['message'] );
		$this->assertEquals( 1, did_action( 'woocommerce_rest_insert_system_status_tool' ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/system_status/tools/not_a_real_tool' ) );
		$this->assertEquals( 404, $response->get_status() );

		// Test _fields for execute system tool request.
		$query_params = array(
			'_fields' => 'id,success,nonexisting',
		);
		$request      = new WP_REST_Request( 'PUT', '/wc/v3/system_status/tools/recount_terms' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertTrue( $data['success'] );

		// Fields that are not requested are not returned in response.
		$this->assertArrayNotHasKey( 'action', $data );
		$this->assertArrayNotHasKey( 'name', $data );
		$this->assertArrayNotHasKey( 'description', $data );
		// Links are part of links, not data in single item response.
		$this->assertArrayNotHasKey( '_links', $data );
		// Non existing field is ignored.
		$this->assertArrayNotHasKey( 'nonexisting', $data );

		// Links are part of links, not data in single item response.
		$links = $response->get_links();
		$this->assertEquals( 1, count( $links ) );
	}

	/**
	 * Test to make sure a tool cannot be run without valid creds.
	 *
	 * @since 3.5.0
	 */
	public function test_execute_system_status_tool_without_permission() {
		wp_set_current_user( self::$no_user );
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/system_status/tools/recount_terms' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test system status schema.
	 *
	 * @since 3.5.0
	 */
	public function test_system_status_tool_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/system_status/tools' );
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

	/**
	 * Provides a mocked response for external requests performed by WC_REST_System_Status_Controller.
	 * This way it is not necessary to perform a regular request to an external server which would
	 * significantly slow down the tests.
	 *
	 * This function is called by WP_HTTP_TestCase::http_request_listner().
	 *
	 * @param array  $request Request arguments.
	 * @param string $url URL of the request.
	 *
	 * @return array|false mocked response or false to let WP perform a regular request.
	 */
	protected function mock_http_responses( $request, $url ) {
		$mocked_response = false;

		if ( in_array( $url, array( 'https://www.paypal.com/cgi-bin/webscr', 'https://woocommerce.com/wc-api/product-key-api?request=ping&network=0' ), true ) ) {
			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		} elseif ( 'https://api.wordpress.org/themes/info/1.0/' === $url ) {
			$mocked_response = array(
				'body'     => 'O:8:"stdClass":12:{s:4:"name";s:7:"Default";s:4:"slug";s:7:"default";s:7:"version";s:5:"1.7.2";s:11:"preview_url";s:29:"https://wp-themes.com/default";s:6:"author";s:15:"wordpressdotorg";s:14:"screenshot_url";s:61:"//ts.w.org/wp-content/themes/default/screenshot.png?ver=1.7.2";s:6:"rating";d:100;s:11:"num_ratings";s:1:"3";s:10:"downloaded";i:296618;s:12:"last_updated";s:10:"2010-06-14";s:8:"homepage";s:37:"https://wordpress.org/themes/default/";s:13:"download_link";s:55:"https://downloads.wordpress.org/theme/default.1.7.2.zip";}',
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}
}
