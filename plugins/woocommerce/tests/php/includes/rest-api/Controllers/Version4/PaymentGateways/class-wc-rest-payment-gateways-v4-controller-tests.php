<?php
/**
 * Payment Gateways V4 Controller tests.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

use Automattic\WooCommerce\RestApi\Routes\V4\PaymentGateways\Controller as PaymentGatewaysController;
use Automattic\WooCommerce\RestApi\Routes\V4\PaymentGateways\Schema\PaymentGatewaySchema;

/**
 * Payment Gateways V4 Controller tests class.
 */
class WC_REST_Payment_Gateways_V4_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Test endpoint.
	 *
	 * @var PaymentGatewaysController
	 */
	protected $endpoint;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected $user;

	/**
	 * Feature enabler callback.
	 *
	 * @var callable
	 */
	private static $feature_enabler;

	/**
	 * Enable the REST API v4 feature.
	 */
	public function enable_rest_api_v4_feature() {
		if ( ! self::$feature_enabler ) {
			self::$feature_enabler = function ( $features ) {
				if ( ! in_array( 'rest-api-v4', $features, true ) ) {
					$features[] = 'rest-api-v4';
				}
				return $features;
			};
		}
		add_filter( 'woocommerce_admin_features', self::$feature_enabler );
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public function disable_rest_api_v4_feature() {
		if ( self::$feature_enabler ) {
			remove_filter( 'woocommerce_admin_features', self::$feature_enabler );
		}
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_rest_api_v4_feature();

		$schema = new PaymentGatewaySchema();

		$this->endpoint = new PaymentGatewaysController();
		$this->endpoint->init( $schema );

		// Manually register ONLY our controller's routes to avoid triggering global REST API init.
		$this->endpoint->register_routes();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		$this->disable_rest_api_v4_feature();
		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/payment-gateways', $routes );
		$this->assertArrayHasKey( '/wc/v4/payment-gateways/(?P<id>[\w-]+)', $routes );
	}

	/**
	 * Test getting all payment gateways.
	 */
	public function test_get_payment_gateways(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways' );
		$response = $this->server->dispatch( $request );
		$gateways = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $gateways );
		$this->assertNotEmpty( $gateways );

		// Find the cheque gateway.
		$cheque = current(
			array_filter(
				$gateways,
				function ( $gateway ) {
					return WC_Gateway_Cheque::ID === $gateway['id'];
				}
			)
		);

		$this->assertIsArray( $cheque );
		$this->assertEquals( WC_Gateway_Cheque::ID, $cheque['id'] );
		$this->assertArrayHasKey( 'title', $cheque );
		$this->assertArrayHasKey( 'description', $cheque );
		$this->assertArrayHasKey( 'enabled', $cheque );
		$this->assertArrayHasKey( 'method_title', $cheque );
		$this->assertArrayHasKey( 'method_description', $cheque );
		$this->assertArrayHasKey( 'method_supports', $cheque );
		$this->assertArrayHasKey( 'settings', $cheque );
		$this->assertIsArray( $cheque['settings'] );
	}

	/**
	 * Test getting payment gateways without permission.
	 */
	public function test_get_payment_gateways_without_permission(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single payment gateway.
	 */
	public function test_get_payment_gateway(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cheque' );
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( WC_Gateway_Cheque::ID, $gateway['id'] );
		$this->assertEquals( 'Check payments', $gateway['method_title'] );
		$this->assertIsArray( $gateway['settings'] );
		$this->assertArrayHasKey( 'title', $gateway['settings'] );
	}

	/**
	 * Test getting a payment gateway without permission.
	 */
	public function test_get_payment_gateway_without_permission(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cheque' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a payment gateway with an invalid ID.
	 */
	public function test_get_payment_gateway_invalid_id(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/invalid_gateway_id' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating a payment gateway.
	 */
	public function test_update_payment_gateway(): void {
		// Get current values.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cheque' );
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 'Check payments', $gateway['settings']['title']['value'] );
		$this->assertFalse( $gateway['enabled'] );

		// Update settings.
		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/cheque' );
		$request->set_body_params(
			array(
				'enabled'  => true,
				'settings' => array(
					'title' => 'Updated Check Payment Title',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $gateway['enabled'] );
		$this->assertEquals( 'Updated Check Payment Title', $gateway['settings']['title']['value'] );
	}

	/**
	 * Test updating payment gateway without permission.
	 */
	public function test_update_payment_gateway_without_permission(): void {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/cheque' );
		$request->set_body_params(
			array(
				'enabled' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating payment gateway with invalid ID.
	 */
	public function test_update_payment_gateway_invalid_id(): void {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/invalid_gateway' );
		$request->set_body_params(
			array(
				'enabled' => true,
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating multiple settings at once.
	 */
	public function test_update_multiple_settings(): void {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/cheque' );
		$request->set_body_params(
			array(
				'enabled'     => true,
				'order'       => 5,
				'title'       => 'Pay by Check',
				'description' => 'Send us a check.',
				'settings'    => array(
					'title'        => 'Check Payment',
					'instructions' => 'Please send check to our office.',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $gateway['enabled'] );
		$this->assertEquals( 5, $gateway['order'] );
		$this->assertEquals( 'Pay by Check', $gateway['title'] );
		$this->assertEquals( 'Send us a check.', $gateway['description'] );
		$this->assertEquals( 'Check Payment', $gateway['settings']['title']['value'] );
		$this->assertEquals( 'Please send check to our office.', $gateway['settings']['instructions']['value'] );
	}

	/**
	 * Test schema.
	 */
	public function test_schema(): void {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/payment-gateways' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertEquals( 'payment-gateway', $schema['title'] );
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
		$this->assertArrayHasKey( 'description', $schema['properties'] );
		$this->assertArrayHasKey( 'order', $schema['properties'] );
		$this->assertArrayHasKey( 'enabled', $schema['properties'] );
		$this->assertArrayHasKey( 'method_title', $schema['properties'] );
		$this->assertArrayHasKey( 'method_description', $schema['properties'] );
		$this->assertArrayHasKey( 'method_supports', $schema['properties'] );
		$this->assertArrayHasKey( 'settings', $schema['properties'] );

		// Verify schema types.
		$this->assertEquals( 'string', $schema['properties']['id']['type'] );
		$this->assertEquals( 'boolean', $schema['properties']['enabled']['type'] );
		$this->assertEquals( 'integer', $schema['properties']['order']['type'] );
		$this->assertEquals( 'object', $schema['properties']['settings']['type'] );
	}

	/**
	 * Test response includes links.
	 */
	public function test_response_includes_links(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cheque' );
		$response = $this->server->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'self', $links );
		$this->assertArrayHasKey( 'collection', $links );
		$this->assertStringContainsString( '/wc/v4/payment-gateways/cheque', $links['self'][0]['href'] );
		$this->assertStringContainsString( '/wc/v4/payment-gateways', $links['collection'][0]['href'] );
	}

	/**
	 * Test updating settings with invalid data.
	 */
	public function test_update_settings_with_invalid_data(): void {
		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/cheque' );
		$request->set_body_params(
			array(
				'settings' => array(
					'invalid_setting' => 'some_value',
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Should still succeed but ignore invalid settings.
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test that order is properly updated.
	 */
	public function test_update_gateway_order(): void {
		// Set initial order.
		$request = new WP_REST_Request( 'PUT', '/wc/v4/payment-gateways/cheque' );
		$request->set_body_params( array( 'order' => 10 ) );
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $gateway['order'] );

		// Verify order is persisted.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cheque' );
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 10, $gateway['order'] );
	}

	/**
	 * Test that COD gateway multiselect options are populated when simulating settings access.
	 */
	public function test_get_cod_gateway_with_shipping_method_options(): void {
		global $wp;

		// Mock the global $wp variable to simulate accessing payment_gateways endpoint.
		// Note: COD gateway checks for '/payment_gateways' (underscore) in the route.
		if ( ! isset( $wp->query_vars ) ) {
			$wp->query_vars = array();
		}
		$wp->query_vars['rest_route'] = '/wc/v4/payment_gateways';

		// Set REST_REQUEST constant if not already defined.
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}

		// Re-initialize the COD gateway to trigger options loading.
		$gateways = WC()->payment_gateways->payment_gateways();
		if ( isset( $gateways['cod'] ) ) {
			$gateways['cod']->init_form_fields();
		}

		$request  = new WP_REST_Request( 'GET', '/wc/v4/payment-gateways/cod' );
		$response = $this->server->dispatch( $request );
		$gateway  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'cod', $gateway['id'] );
		$this->assertArrayHasKey( 'settings', $gateway );
		$this->assertArrayHasKey( 'enable_for_methods', $gateway['settings'] );

		// Verify the multiselect field has options populated.
		$enable_for_methods = $gateway['settings']['enable_for_methods'];
		$this->assertEquals( 'multiselect', $enable_for_methods['type'] );
		$this->assertArrayHasKey( 'options', $enable_for_methods );
		$this->assertNotEmpty( $enable_for_methods['options'], 'Options should be populated when is_accessing_settings() returns true' );
	}
}
