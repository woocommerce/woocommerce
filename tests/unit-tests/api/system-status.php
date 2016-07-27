<?php
/**
 * System Status REST Tests
 * @package WooCommerce\Tests\API
 * @since 2.7
 */
class WC_Tests_REST_System_Status extends WC_Unit_Test_Case {

    protected $server;

    /**
    * Setup our test server.
    */
    public function setUp() {
        parent::setUp();
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_Test_Spy_REST_Server;
        do_action( 'rest_api_init' );
    }

    /**
    * Unset the server.
    */
    public function tearDown() {
        parent::tearDown();
        global $wp_rest_server;
        $wp_rest_server = null;
    }

    /**
     * Test route registration.
     */
    public function test_register_routes() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey( '/wc/v1/system-status', $routes );
    }


}
