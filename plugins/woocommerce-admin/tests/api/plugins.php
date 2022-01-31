<?php
/**
 * Plugins REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\Plugins;
use Automattic\WooCommerce\Admin\PaymentMethodSuggestionsDataSourcePoller;

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
	 * Test that scheduling a plugin install works.
	 */
	public function test_install_plugin_async() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'async'   => true,
				'plugins' => 'facebook-for-woocommerce',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$plugins  = get_plugins();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'job_id', $data['data'] );
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
	 * Test that scheduling a plugin activation works.
	 */
	public function test_activate_plugin_async() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'async'   => true,
				'plugins' => 'facebook-for-woocommerce',
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
	 * Test that recommended payment plugins are returned correctly.
	 */
	public function test_get_recommended_payment_plugins() {
		wp_set_current_user( $this->user );
		set_transient(
			'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs',
			array(
				(object) array(
					'plugins' => array( 'plugin' ),
					'title'   => 'test',
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'plugin', $data[0]->plugins[0] );
		delete_transient( 'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs' );
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
		update_option( 'active_plugins', array( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php' ) );
		set_transient(
			'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs',
			array(
				(object) array(
					'plugins'    => array( 'woocommerce-gateway-stripe' ),
					'title'      => 'test',
					'is_visible' => array(
						(object) array(
							'type'    => 'not',
							'operand' => array(
								(object) array(
									'type'    => 'plugins_activated',
									'plugins' => array( 'woocommerce-gateway-stripe' ),
								),
							),
						),
					),
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 0, count( $data ) );
		delete_transient( 'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs' );

		delete_option( 'active_plugins' );
	}

	/**
	 * Test that recommended payment plugins are not returned when active.
	 */
	public function test_plugins_when_dismissed_is_set_to_yes() {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_setting_payments_recommendations_hidden', 'yes' );
		set_transient(
			'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs',
			array(
				(object) array(
					'plugins' => array( 'plugin' ),
					'title'   => 'test',
				),
			)
		);

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/recommended-payment-plugins' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 0, count( $data ) );
		delete_transient( 'woocommerce_admin_' . PaymentMethodSuggestionsDataSourcePoller::ID . '_specs' );
		delete_option( 'woocommerce_setting_payments_recommendations_hidden' );
	}

	/**
	 * Test dismissing recommended payment plugins endpoint.
	 */
	public function test_dismiss_recommended_payment_plugins() {
		$this->assertEquals( 'no', get_option( 'woocommerce_setting_payments_recommendations_hidden', 'no' ) );
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/recommended-payment-plugins/dismiss' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( true, $data );

		$this->assertEquals( 'yes', get_option( 'woocommerce_setting_payments_recommendations_hidden' ) );
		delete_option( 'woocommerce_setting_payments_recommendations_hidden' );
	}
}
