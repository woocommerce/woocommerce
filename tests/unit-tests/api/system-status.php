<?php
/**
 * System Status REST Tests
 * @package WooCommerce\Tests\API
 * @since 2.7
 */
class WC_Tests_REST_System_Status extends WC_REST_Unit_Test_Case {

    /**
     * Setup our test server.
     */
    public function setUp() {
        parent::setUp();
        $this->endpoint = new WC_REST_System_Status_Controller();
        $this->user = $this->factory->user->create( array(
            'role' => 'administrator',
        ) );
    }

    /**
     * Test route registration.
     */
    public function test_register_routes() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey( '/wc/v1/system_status', $routes );
    }

    /**
     * Test to make sure system status cannot be accessed without valid creds
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_without_permission() {
        wp_set_current_user( 0 );
        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $this->assertEquals( 401, $response->get_status() );
    }

    /**
     * Test to make sure root properties are present.
     * (environment, theme, database, etc).
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_returns_root_properties() {
        wp_set_current_user( $this->user );
        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data = $response->get_data();

        $this->assertArrayHasKey( 'environment', $data );
        $this->assertArrayHasKey( 'database', $data );
        $this->assertArrayHasKey( 'active_plugins', $data );
        $this->assertArrayHasKey( 'theme', $data );
        $this->assertArrayHasKey( 'settings', $data );
        $this->assertArrayHasKey( 'pages', $data );
    }

    /**
     * Test to make sure environment response is correct.
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_environment() {
        wp_set_current_user( $this->user );
        $response    = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data        = $response->get_data();
        $environment = $data['environment'];

        // Make sure all expected data is present
        $this->assertEquals( 30, count( $environment ) );

        // Test some responses to make sure they match up
        $this->assertEquals( get_option( 'home' ), $environment['home_url'] );
        $this->assertEquals( get_option( 'siteurl' ), $environment['site_url'] );
        $this->assertEquals( WC()->version, $environment['version'] );
    }

    /**
     * Test to make sure database response is correct.
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_database() {
        global $wpdb;
        wp_set_current_user( $this->user );
        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data     = $response->get_data();
        $database = $data['database'];

        $this->assertEquals( get_option( 'woocommerce_db_version' ), $database['wc_database_version'] );
        $this->assertEquals( $wpdb->prefix, $database['database_prefix'] );
        $this->assertEquals( WC_Geolocation::get_local_database_path(), $database['maxmind_geoip_database'] );
        $this->assertArrayHasKey( 'woocommerce_payment_tokens', $database['database_tables'] );
    }

    /**
     * Test to make sure active plugins response is correct.
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_active_plugins() {
        wp_set_current_user( $this->user );

        $actual_plugins = array( 'hello.php' );
        update_option( 'active_plugins', $actual_plugins );
        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        update_option( 'active_plugins', array() );

        $data    = $response->get_data();
        $plugins = $data['active_plugins'];

        $this->assertEquals( 1, count( $plugins ) );
        $this->assertEquals( 'Hello Dolly', $plugins[0]['name'] );
    }

    /**
     * Test to make sure theme response is correct.
     *
     * @since 2.7.0
    */
    public function test_get_system_status_info_theme() {
        wp_set_current_user( $this->user );
    	$active_theme = wp_get_theme();

        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data     = $response->get_data();
        $theme    = $data['theme'];

        $this->assertEquals( 9, count( $theme ) );
        $this->assertEquals( $active_theme->Name, $theme['name'] );
    }

    /**
     * Test to make sure settings response is correct.
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_settings() {
        wp_set_current_user( $this->user );

        $term_response = array();
        $terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
        foreach ( $terms as $term ) {
            $term_response[ $term->slug ] = strtolower( $term->name );
        }

        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data     = $response->get_data();
        $settings = $data['settings'];

        $this->assertEquals( 10, count( $settings ) );
        $this->assertEquals( ( 'yes' === get_option( 'woocommerce_api_enabled' ) ), $settings['api_enabled'] );
        $this->assertEquals(  get_woocommerce_currency(), $settings['currency'] );
        $this->assertEquals( $term_response, $settings['taxonomies'] );
    }

    /**
     * Test to make sure pages response is correct.
     *
     * @since 2.7.0
     */
    public function test_get_system_status_info_pages() {
        wp_set_current_user( $this->user );
        $response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/system_status' ) );
        $data     = $response->get_data();
        $pages = $data['pages'];
        $this->assertEquals( 4, count( $pages ) );
    }

    /**
     * Test system status schema.
     *
     * @since 2.7.0
    */
    public function test_system_status_schema() {
        $request = new WP_REST_Request( 'OPTIONS', '/wc/v1/system_status' );
        $response = $this->server->dispatch( $request );
        $data = $response->get_data();
        $properties = $data['schema']['properties'];
        $this->assertEquals( 6, count( $properties ) );
        $this->assertArrayHasKey( 'environment', $properties );
        $this->assertArrayHasKey( 'database', $properties );
        $this->assertArrayHasKey( 'active_plugins', $properties );
        $this->assertArrayHasKey( 'theme', $properties );
        $this->assertArrayHasKey( 'settings', $properties );
        $this->assertArrayHasKey( 'pages', $properties );
    }

}
