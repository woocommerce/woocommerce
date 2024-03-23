<?php
/**
 * Class WC_Tests_REST_System_Status file.
 *
 * @package Automattic/WooCommerce/Tests
 */

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * System Status REST Tests.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_REST_System_Status extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

	/**
	 * User variable.
	 *
	 * @var int
	 */
	protected static int $administrator_user;

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
			$response                             = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/system_status' ) );
			$data                                 = $response->get_data();
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
		$this->assertArrayHasKey( 'environment', $system_status_data );
		$this->assertArrayHasKey( 'database', $system_status_data );
		$this->assertArrayHasKey( 'active_plugins', $system_status_data );
		$this->assertArrayHasKey( 'theme', $system_status_data );
		$this->assertArrayHasKey( 'settings', $system_status_data );
		$this->assertArrayHasKey( 'security', $system_status_data );
		$this->assertArrayHasKey( 'pages', $system_status_data );
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
		$this->assertEquals( 33, count( $environment ) );

		// Test some responses to make sure they match up.
		$this->assertEquals( get_option( 'home' ), $environment['home_url'] );
		$this->assertEquals( get_option( 'siteurl' ), $environment['site_url'] );
		$this->assertEquals( get_option( \WC_Install::STORE_ID_OPTION, null ), $environment['store_id'] );
		$this->assertEquals( $store_id, $environment['store_id'] );
		$this->assertEquals( $version, $environment['version'] );
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
				'version' => $version,
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
		$actual_plugins = array( 'hello.php' );
		update_option( 'active_plugins', $actual_plugins );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status' ) );
		update_option( 'active_plugins', array() );

		$data    = $response->get_data();
		$plugins = (array) $data['active_plugins'];

		$this->assertEquals( 1, count( $plugins ) );
		$this->assertEquals( 'Hello Dolly', $plugins[0]['name'] );
	}

	/**
	 * Test to make sure theme response is correct.
	 *
	 * @since 3.5.0
	 */
	public function test_get_system_status_info_theme() {
		$active_theme = wp_get_theme();
		$theme        = (array) $this->fetch_or_get_system_status_data_for_user( self::$administrator_user )['theme'];

		$this->assertEquals( 13, count( $theme ) );
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
		$this->assertEquals( ( 'yes' === get_option( 'woocommerce_api_enabled' ) ), $settings['api_enabled'] );
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
		$this->assertEquals( 11, count( $properties ) );
		$this->assertArrayHasKey( 'environment', $properties );
		$this->assertArrayHasKey( 'database', $properties );
		$this->assertArrayHasKey( 'active_plugins', $properties );
		$this->assertArrayHasKey( 'theme', $properties );
		$this->assertArrayHasKey( 'settings', $properties );
		$this->assertArrayHasKey( 'security', $properties );
		$this->assertArrayHasKey( 'pages', $properties );
		$this->assertArrayHasKey( 'logging', $properties );
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

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $raw_tools ), count( $data ) );

		$matching_tool_data = current(
			array_filter(
				$data,
				function( $tool ) {
					return 'regenerate_thumbnails' === $tool['id'];
				}
			)
		);
		$this->assertIsArray( $matching_tool_data );

		$this->assertArraySubset(
			array(
				'id'          => 'regenerate_thumbnails',
				'name'        => 'Regenerate shop thumbnails',
				'action'      => 'Regenerate',
				'description' => 'This will regenerate all shop thumbnails to match your theme and/or image settings.',
				'_links'      => array(
					'item' => array(
						array(
							'href'       => rest_url( '/wc/v3/system_status/tools/regenerate_thumbnails' ),
							'embeddable' => 1,
						),
					),
				),
			),
			$matching_tool_data
		);

		$query_params = array(
			'_fields' => 'id,name,nonexisting',
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v3/system_status/tools' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( count( $raw_tools ), count( $data ) );

		$matching_tool_data = current(
			array_filter(
				$data,
				function( $tool ) {
					return 'regenerate_thumbnails' === $tool['id'];
				}
			)
		);
		$this->assertIsArray( $matching_tool_data );

		$this->assertArraySubset(
			array(
				'id'   => 'regenerate_thumbnails',
				'name' => 'Regenerate shop thumbnails',
			),
			$matching_tool_data
		);
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
		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();
		$raw_tool         = $raw_tools['recount_terms'];

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/system_status/tools/recount_terms' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertEquals( 'Term counts', $data['name'] );
		$this->assertEquals( 'Recount terms', $data['action'] );
		$this->assertEquals( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', $data['description'] );

		// Test for _fields query parameter.
		$query_params = array(
			'_fields' => 'id,name,nonexisting',
		);
		$request      = new WP_REST_Request( 'GET', '/wc/v3/system_status/tools/recount_terms' );
		$request->set_query_params( $query_params );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertEquals( 'Term counts', $data['name'] );
		$this->assertArrayNotHasKey( 'action', $data );
		$this->assertArrayNotHasKey( 'description', $data );
		// Links are part of links, not data in single items.
		$this->assertArrayNotHasKey( '_links', $data );

		// Links are part of links, not data in single item response.
		$links = $response->get_links();
		$this->assertEquals( 1, count( $links ) );
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
		$tools_controller = new WC_REST_System_Status_Tools_Controller();
		$raw_tools        = $tools_controller->get_tools();
		$raw_tool         = $raw_tools['recount_terms'];

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/system_status/tools/recount_terms' ) );
		$data     = $response->get_data();

		$this->assertEquals( 'recount_terms', $data['id'] );
		$this->assertEquals( 'Term counts', $data['name'] );
		$this->assertEquals( 'Recount terms', $data['action'] );
		$this->assertEquals( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', $data['description'] );
		$this->assertTrue( $data['success'] );
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

		if ( in_array( $url, array( 'https://www.paypal.com/cgi-bin/webscr', 'https://woo.com/wc-api/product-key-api?request=ping&network=0' ), true ) ) {
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
