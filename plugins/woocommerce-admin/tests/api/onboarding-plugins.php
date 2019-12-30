<?php
/**
 * Onboarding Plugins REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingPlugins;
use \Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * WC Tests API Onboarding Plugins
 */
class WC_Tests_API_Onboarding_Plugins extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/plugins';

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
				'plugin' => 'facebook-for-woocommerce',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$plugins  = get_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'facebook-for-woocommerce', $data['slug'] );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertArrayHasKey( 'facebook-for-woocommerce/facebook-for-woocommerce.php', $plugins );
	}

	/**
	 * Test that installing an invalid plugin fails.
	 */
	public function test_install_invalid_plugin() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'plugin' => 'invalid-plugin-name',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_plugin', $data['code'] );
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
		$active_plugins = Onboarding::get_active_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains( 'facebook-for-woocommerce', $data['activatedPlugins'] );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertContains( 'facebook-for-woocommerce', $active_plugins );
	}

	/**
	 * Test that activating an invalid plugin fails.
	 */
	public function test_activate_invalid_plugin() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'plugins' => 'invalid-plugin-name',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_plugins', $data['code'] );
	}
}
