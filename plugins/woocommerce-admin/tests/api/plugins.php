<?php
/**
 * Plugins REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\Plugins;

/**
 * WC Tests API Plugins
 */
class WC_Tests_API_Plugins extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/plugins';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
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

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'plugins' => 'facebook-for-woocommerce',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$plugins  = get_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'facebook-for-woocommerce' ), $data['data']['installed'] );
		$this->assertEquals( true, $data['success'] );
		$this->assertArrayHasKey( 'facebook-for-woocommerce/facebook-for-woocommerce.php', $plugins );
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
				'plugins' => 'facebook-for-woocommerce',
			)
		);
		$response       = $this->server->dispatch( $request );
		$data           = $response->get_data();
		$active_plugins = Plugins::get_active_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( 'facebook-for-woocommerce', $data['data']['activated'] );
		$this->assertEquals( true, $data['success'] );
		$this->assertContains( 'facebook-for-woocommerce', $active_plugins );
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
}
