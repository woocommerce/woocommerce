<?php
/**
 * WC REST API Unit Test Case (for WP-API Endpoints).
 *
 * Provides REST API specific methods and setup/teardown.
 *
 * @since 3.0
 */

class WC_REST_Unit_Test_Case extends WC_Unit_Test_Case {

	protected $server;

	/**
	 * Setup our test server.
	 */
	public function setUp() {
		parent::setUp();
		global $wp_rest_server;
		$wp_rest_server = new WP_Test_Spy_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		// Reset payment gateways.
		$gateways = WC_Payment_Gateways::instance();
		$gateways->payment_gateways = array();
		$gateways->init();
	}

	/**
	 * Unset the server.
	 */
	public function tearDown() {
		parent::tearDown();
		global $wp_rest_server;
		unset( $this->server );
		$wp_rest_server = null;
	}
}
