<?php
/**
 * Tests for the Payment Gateways REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Payment gateway test class.
 */
class Payment_Gateways extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
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
		$this->assertSame( 200, $response->get_status() );
		$gateways = $response->get_data();
		$this->assertIsArray( $gateways );

		$gateways_by_id = array_column( $gateways, null, 'id' );
		$setting_types  = array(
			WC_Gateway_BACS::ID   => array(
				'title'        => 'safe_text',
				'instructions' => 'textarea',
			),
			WC_Gateway_Cheque::ID => array(
				'title'        => 'safe_text',
				'instructions' => 'textarea',
			),
			WC_Gateway_COD::ID    => array(
				'title'              => 'safe_text',
				'instructions'       => 'textarea',
				'enable_for_methods' => 'multiselect',
				'enable_for_virtual' => 'checkbox',
			),
		);

		foreach ( $setting_types as $gateway_id => $expected_settings ) {
			$this->assertArrayHasKey( $gateway_id, $gateways_by_id );

			$gateway = $gateways_by_id[ $gateway_id ];
			$this->assertSame( $gateway_id, $gateway['id'] );
			$this->assertIsBool( $gateway['enabled'] );
			$this->assertContains( 'products', $gateway['method_supports'] );
			$this->assertSame( rest_url( '/wc/v3/payment_gateways/' . $gateway_id ), $gateway['_links']['self'][0]['href'] );
			$this->assertSame( rest_url( '/wc/v3/payment_gateways' ), $gateway['_links']['collection'][0]['href'] );

			foreach ( $expected_settings as $setting_id => $setting_type ) {
				$this->assertArrayHasKey( $setting_id, $gateway['settings'] );
				$this->assertSame( $setting_id, $gateway['settings'][ $setting_id ]['id'] );
				$this->assertSame( $setting_type, $gateway['settings'][ $setting_id ]['type'] );
			}
		}

		$matching_gateway_data = current(
			array_filter(
				$gateways,
				function( $gateway ) {
					return WC_Gateway_Cheque::ID === $gateway['id'];
				}
			)
		);
		$this->assertIsArray( $matching_gateway_data );

		$this->assertArraySubset(
			array(
				'id'                     => WC_Gateway_Cheque::ID,
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
				'settings_url'           => 'http://' . WP_TESTS_DOMAIN . '/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/offline/cheque',
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
			$matching_gateway_data
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
				'id'                     => WC_Gateway_Paypal::ID,
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
				'settings_url'           => 'http://' . WP_TESTS_DOMAIN . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=paypal',
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

		// Test bogus parameter.
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
	 * Test that updating a gateway's enabled state persists across a fresh registry.
	 */
	public function test_update_payment_gateway_enabled_state() {
		wp_set_current_user( $this->user );

		$woocommerce                        = WC();
		$gateway                            = new WC_Gateway_Cheque();
		$option_key                         = $gateway->get_option_key();
		$missing_option                     = new stdClass();
		$original_option                    = get_option( $option_key, $missing_option );
		$option_existed                     = $missing_option !== $original_option;
		$woocommerce_properties             = get_object_vars( $woocommerce );
		$had_payment_gateways_property      = array_key_exists( 'payment_gateways', $woocommerce_properties );
		$original_payment_gateways_property = $had_payment_gateways_property ? $woocommerce_properties['payment_gateways'] : null;
		$paypal_instance_property           = new ReflectionProperty( WC_Gateway_Paypal::class, 'instance' );
		$paypal_instance_property->setAccessible( true );
		$original_paypal_instance = $paypal_instance_property->getValue();

		$disabled_settings = is_array( $original_option ) ? $original_option : array();

		$disabled_settings['enabled'] = 'no';

		try {
			update_option( $option_key, $disabled_settings );
			$woocommerce->payment_gateways = new WC_Payment_Gateways();

			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/cheque' ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertFalse( $data['enabled'] );

			$request = new WP_REST_Request( 'POST', '/wc/v3/payment_gateways/cheque' );
			$request->set_body_params(
				array(
					'enabled' => true,
				)
			);
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertTrue( $data['enabled'] );

			$woocommerce->payment_gateways = new WC_Payment_Gateways();

			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/cheque' ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertTrue( $data['enabled'] );

			update_option( $option_key, $disabled_settings );
			$woocommerce->payment_gateways = new WC_Payment_Gateways();

			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payment_gateways/cheque' ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertFalse( $data['enabled'] );
		} finally {
			if ( $option_existed ) {
				update_option( $option_key, $original_option );
			} else {
				delete_option( $option_key );
			}

			if ( $had_payment_gateways_property ) {
				$woocommerce->payment_gateways = $original_payment_gateways_property;
			} else {
				unset( $woocommerce->payment_gateways );
			}

			WC_Gateway_Paypal::set_instance( $original_paypal_instance );
		}
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
