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

	/**
	 * Test that recommended payment plugins are returned correctly.
	 */
	public function test_get_recommended_payment_plugins() {
		wp_set_current_user( $this->user );
		set_transient(
			\Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT,
			array(
				'recommendations' => array(
					array(
						'product' => 'plugin',
						'title'   => 'test',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'plugin', $data[0]['product'] );
		delete_transient( \Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT );
	}

	/**
	 * Test that recommended payment plugins with locale data.
	 */
	public function test_get_recommended_payment_plugins_with_locale() {
		wp_set_current_user( $this->user );
		add_filter( 'locale', array( $this, 'set_france_locale' ) );
		set_transient(
			\Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT,
			array(
				'recommendations' => array(
					array(
						'product'     => 'plugin',
						'title'       => 'test',
						'locale-data' => array(
							'fr_FR' => array(
								'title' => 'translated title',
							),
						),
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'plugin', $data[0]['product'] );
		$this->assertEquals( 'translated title', $data[0]['title'] );
		$this->assertEquals( false, isset( $data[0]['locale-data'] ) );
		delete_transient( \Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT );
		remove_filter( 'locale', array( $this, 'set_france_locale' ) );
	}

	/**
	 * Test that recommended payment plugins with not default supported locale.
	 */
	public function test_get_recommended_payment_plugins_with_not_supported_locale() {
		wp_set_current_user( $this->user );
		add_filter( 'locale', array( $this, 'set_france_locale' ) );
		set_transient(
			\Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT,
			array(
				'recommendations' => array(
					array(
						'product' => 'plugin',
						'title'   => 'test',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Return nothing as default is only english locales.
		$this->assertEquals( 0, count( $data ) );
		delete_transient( \Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT );
		remove_filter( 'locale', array( $this, 'set_france_locale' ) );
	}

	/**
	 * @return string locale
	 */
	public function set_france_locale() {
		return 'fr_FR';
	}

	/**
	 * Test that recommended payment plugins are not returned when active.
	 */
	public function test_get_recommended_payment_plugins_that_are_active() {
		wp_set_current_user( $this->user );
		update_option( 'active_plugins', array( 'facebook-for-woocommerce/facebook-for-woocommerce.php' ) );
		set_transient(
			\Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT,
			array(
				'recommendations' => array(
					array(
						'product' => 'facebook-for-woocommerce',
						'title'   => 'test',
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 0, count( $data ) );
		delete_transient( \Automattic\WooCommerce\Admin\PaymentPlugins::RECOMMENDED_PLUGINS_TRANSIENT );
		delete_option( 'active_plugins' );
	}
}
