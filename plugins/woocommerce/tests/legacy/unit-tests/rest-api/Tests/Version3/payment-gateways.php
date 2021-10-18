<?php
/**
 * Tests for the Payment Gateways REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

/**
 * Payment gateway test class.
 */
class Payment_Gateways extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Payment_Gateways_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/payment_gateways', $routes );
		$this->assertArrayHasKey( '/wc/v3/payment_gateways/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test getting all payment gateways.
	 *
	 * @since 3.5.0
	 */
	public function test_get_payment_gateways() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways' ) );
		$gateways = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertContains(
			array(
				'id'                     => 'cheque',
				'title'                  => 'Check payments',
				'description'            => 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.',
				'order'                  => '',
				'enabled'                => false,
				'method_title'           => 'Check payments',
				'method_description'     => 'Take payments in person via checks. This offline gateway can also be useful to test purchases.',
				'method_supports'        => array(
					'products',
				),
				'settings'               => array_diff_key(
					$this->get_settings( 'WC_Gateway_Cheque' ),
					array(
						'enabled'     => false,
						'description' => false,
					)
				),
				'needs_setup'            => false,
				'post_install_scripts'   => array(),
				'settings_url'           => 'http://example.org/wp-admin/admin.php?page=wc-settings&tab=checkout&section=cheque',
				'connection_url'         => '',
				'setup_help_text'        => '',
				'required_settings_keys' => array(),
				'_links'                 => array(
					'self'       => array(
						array(
							'href' => rest_url( '/wc/v3/payment_gateways/cheque' ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v3/payment_gateways' ),
						),
					),
				),
			),
			$gateways
		);
	}

	/**
	 * Tests to make sure payment gateways cannot viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_payment_gateways_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single payment gateway.
	 *
	 * @since 3.5.0
	 */
	public function test_get_payment_gateway() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/paypal' ) );
		$paypal   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                     => 'paypal',
				'title'                  => 'PayPal',
				'description'            => "Pay via PayPal; you can pay with your credit card if you don't have a PayPal account.",
				'order'                  => '',
				'enabled'                => false,
				'method_title'           => 'PayPal Standard',
				'method_description'     => 'PayPal Standard redirects customers to PayPal to enter their payment information.',
				'method_supports'        => array(
					'products',
					'refunds',
				),
				'settings'               => array_diff_key(
					$this->get_settings( 'WC_Gateway_Paypal' ),
					array(
						'enabled'     => false,
						'description' => false,
					)
				),
				'needs_setup'            => false,
				'post_install_scripts'   => array(),
				'settings_url'           => 'http://example.org/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paypal',
				'connection_url'         => null,
				'setup_help_text'        => null,
				'required_settings_keys' => array(),
			),
			$paypal
		);
	}

	/**
	 * Test getting a payment gateway without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_payment_gateway_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/paypal' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a payment gateway with an invalid id.
	 *
	 * @since 3.5.0
	 */
	public function test_get_payment_gateway_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/totally_fake_method' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating a single payment gateway.
	 *
	 * @since 3.5.0
	 */
	public function test_update_payment_gateway() {
		wp_set_current_user( $this->user );

		// Test defaults.
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/paypal' ) );
		$paypal   = $response->get_data();

		$this->assertEquals( 'PayPal', $paypal['settings']['title']['value'] );
		$this->assertEquals( 'admin@example.org', $paypal['settings']['email']['value'] );
		$this->assertEquals( 'no', $paypal['settings']['testmode']['value'] );

		// Test updating single setting.
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'settings' => array(
					'email' => 'woo@woo.local',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$paypal   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'PayPal', $paypal['settings']['title']['value'] );
		$this->assertEquals( 'woo@woo.local', $paypal['settings']['email']['value'] );
		$this->assertEquals( 'no', $paypal['settings']['testmode']['value'] );

		// Test updating multiple settings.
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'settings' => array(
					'testmode' => 'yes',
					'title'    => 'PayPal - New Title',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$paypal   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'PayPal - New Title', $paypal['settings']['title']['value'] );
		$this->assertEquals( 'woo@woo.local', $paypal['settings']['email']['value'] );
		$this->assertEquals( 'yes', $paypal['settings']['testmode']['value'] );

		// Test other parameters, and recheck settings.
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'enabled' => false,
				'order'   => 2,
			)
		);
		$response = $this->server->dispatch( $request );
		$paypal   = $response->get_data();

		$this->assertFalse( $paypal['enabled'] );
		$this->assertEquals( 2, $paypal['order'] );
		$this->assertEquals( 'PayPal - New Title', $paypal['settings']['title']['value'] );
		$this->assertEquals( 'woo@woo.local', $paypal['settings']['email']['value'] );
		$this->assertEquals( 'yes', $paypal['settings']['testmode']['value'] );

		// Test bogus paramter.
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'settings' => array(
					'paymentaction' => 'afasfasf',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'settings' => array(
					'paymentaction' => 'authorization',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$paypal   = $response->get_data();
		$this->assertEquals( 'authorization', $paypal['settings']['paymentaction']['value'] );
	}

	/**
	 * Test updating a payment gateway without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_payment_gateway_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/paypal' );
		$request->set_body_params(
			array(
				'settings' => array(
					'testmode' => 'yes',
					'title'    => 'PayPal - New Title',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a payment gateway with an invalid id.
	 *
	 * @since 3.5.0
	 */
	public function test_update_payment_gateway_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/totally_fake_method' );
		$request->set_body_params(
			array(
				'enabled' => true,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test the payment gateway schema.
	 *
	 * @since 3.5.0
	 */
	public function test_payment_gateway_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/payment_gateways' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 9, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'order', $properties );
		$this->assertArrayHasKey( 'enabled', $properties );
		$this->assertArrayHasKey( 'method_title', $properties );
		$this->assertArrayHasKey( 'method_description', $properties );
		$this->assertArrayHasKey( 'method_supports', $properties );
		$this->assertArrayHasKey( 'settings', $properties );
	}

	/**
	 * Loads a particular gateway's settings so we can correctly test API output.
	 *
	 * @since 3.5.0
	 * @param string $gateway_class Name of WC_Payment_Gateway class.
	 */
	private function get_settings( $gateway_class ) {
		$gateway  = new $gateway_class();
		$settings = array();
		$gateway->init_form_fields();
		foreach ( $gateway->form_fields as $id => $field ) {
			// Make sure we at least have a title and type.
			if ( empty( $field['title'] ) || empty( $field['type'] ) ) {
				continue;
			}
			// Ignore 'enabled' and 'description', to be in line with \WC_REST_Payment_Gateways_Controller::get_settings.
			if ( in_array( $id, array( 'enabled', 'description' ), true ) ) {
				continue;
			}
			$data = array(
				'id'          => $id,
				'label'       => empty( $field['label'] ) ? $field['title'] : $field['label'],
				'description' => empty( $field['description'] ) ? '' : $field['description'],
				'type'        => $field['type'],
				'value'       => $gateway->settings[ $id ],
				'default'     => empty( $field['default'] ) ? '' : $field['default'],
				'tip'         => empty( $field['description'] ) ? '' : $field['description'],
				'placeholder' => empty( $field['placeholder'] ) ? '' : $field['placeholder'],
			);
			if ( ! empty( $field['options'] ) ) {
				$data['options'] = $field['options'];
			}
			$settings[ $id ] = $data;
		}
		return $settings;
	}

}
