<?php
/**
 * Offline Payment Methods V4 Controller tests.
 *
 * @package WooCommerce\Tests\API
 */

declare( strict_types=1 );

use Automattic\WooCommerce\RestApi\Routes\V4\OfflinePaymentMethods\Controller as OfflinePaymentMethodsController;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

/**
 * Offline Payment Methods V4 Controller tests class.
 */
class WC_REST_Offline_Payment_Methods_V4_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * Test endpoint.
	 *
	 * @var OfflinePaymentMethodsController
	 */
	protected $endpoint;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected $user;

	/**
	 * Payments instance.
	 *
	 * @var Payments
	 */
	protected $payments;

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			}
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public static function disable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_rest_api_v4_feature();

		$this->payments = wc_get_container()->get( Payments::class );
		$schema         = new Automattic\WooCommerce\RestApi\Routes\V4\OfflinePaymentMethods\OfflinePaymentMethodSchema();

		$this->endpoint = new OfflinePaymentMethodsController();
		$this->endpoint->init( $this->payments, $schema );

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Reset the entire container to clear all cached services and state.
		$this->reset_container_resolutions();

		// Disable feature flag.
		$this->disable_rest_api_v4_feature();

		// Always call parent last.
		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/payments/offline-methods', $routes );
		$this->assertCount( 1, $routes['/wc/v4/payments/offline-methods'] );
	}

	/**
	 * Test getting offline payment methods without location parameter.
	 */
	public function test_get_offline_payment_methods_without_location() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		// Verify each item has the expected offline payment method type.
		foreach ( $data as $item ) {
			$this->assertEquals( PaymentsProviders::TYPE_OFFLINE_PM, $item['_type'] );
			$this->assertArrayHasKey( 'id', $item );
			$this->assertArrayHasKey( 'title', $item );
			$this->assertArrayHasKey( 'description', $item );
		}
	}

	/**
	 * Test getting offline payment methods with location parameter.
	 */
	public function test_get_offline_payment_methods_with_location() {
		$request = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$request->set_param( 'location', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		// Verify each item has the expected offline payment method type.
		foreach ( $data as $item ) {
			$this->assertEquals( PaymentsProviders::TYPE_OFFLINE_PM, $item['_type'] );
		}
	}

	/**
	 * Test getting offline payment methods with invalid location parameter.
	 */
	public function test_get_offline_payment_methods_with_invalid_location() {
		$request = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$request->set_param( 'location', 'INVALID' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Should still return 200 as the Payments service handles invalid locations gracefully.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
	}

	/**
	 * Test permissions for unauthenticated user.
	 */
	public function test_get_offline_payment_methods_without_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test permissions for user without manage_woocommerce capability.
	 */
	public function test_get_offline_payment_methods_with_insufficient_permission() {
		$user = $this->factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		wp_set_current_user( $user );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test the collection schema.
	 */
	public function test_get_collection_schema() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'schema', $data );

		$schema = $data['schema'];
		$this->assertEquals( 'array', $schema['type'] );
		$this->assertArrayHasKey( 'items', $schema );

		// Verify the item schema has all expected properties.
		$item_schema = $schema['items'];
		$this->assertArrayHasKey( 'properties', $item_schema );

		$properties          = $item_schema['properties'];
		$expected_properties = array( 'id', '_order', '_type', 'title', 'description', 'supports', 'plugin', 'image', 'icon', 'links', 'state', 'management', 'onboarding' );

		foreach ( $expected_properties as $property ) {
			$this->assertArrayHasKey( $property, $properties, "Missing property: {$property}" );
		}
	}

	/**
	 * Test the collection parameters.
	 */
	public function test_get_collection_params() {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'endpoints', $data );
		$this->assertArrayHasKey( 0, $data['endpoints'] );
		$this->assertArrayHasKey( 'args', $data['endpoints'][0] );

		$args = $data['endpoints'][0]['args'];
		$this->assertArrayHasKey( 'location', $args );
		$this->assertEquals( 'string', $args['location']['type'] );
		$this->assertFalse( $args['location']['required'] );
	}

	/**
	 * Test response structure matches schema.
	 */
	public function test_response_structure_matches_schema() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		if ( ! empty( $data ) ) {
			$first_item = $data[0];

			// Test required fields are present.
			$this->assertArrayHasKey( 'id', $first_item );
			$this->assertArrayHasKey( '_type', $first_item );
			$this->assertArrayHasKey( 'title', $first_item );

			// Test _type is correct for offline payment methods.
			$this->assertEquals( PaymentsProviders::TYPE_OFFLINE_PM, $first_item['_type'] );

			// Test data types.
			$this->assertIsString( $first_item['id'] );
			$this->assertIsString( $first_item['title'] );

			if ( isset( $first_item['_order'] ) ) {
				$this->assertIsInt( $first_item['_order'] );
			}
		}
	}

	/**
	 * Test that response uses proper preparation methods.
	 */
	public function test_response_uses_proper_preparation() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Test that response has proper links structure if settings links are present.
		$data = $response->get_data();
		if ( ! empty( $data ) ) {
			$links = $response->get_links();
			$this->assertIsArray( $links );
		}
	}

	/**
	 * Test that results are sorted by _order field.
	 */
	public function test_results_are_sorted_by_order() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		// Test that results are sorted by _order field.
		if ( count( $data ) > 1 ) {
			$orders        = array_column( $data, '_order' );
			$sorted_orders = $orders;
			sort( $sorted_orders );
			$this->assertEquals( $sorted_orders, $orders, 'Results should be sorted by _order field' );
		}
	}

	/**
	 * Test _fields parameter filtering.
	 */
	public function test_fields_parameter_filtering() {
		$request = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$request->set_param( '_fields', 'id,title' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		if ( ! empty( $data ) ) {
			$first_item = $data[0];

			// Should only contain the requested fields.
			$this->assertArrayHasKey( 'id', $first_item );
			$this->assertArrayHasKey( 'title', $first_item );

			// Should NOT contain other fields like description, _type, etc.
			$this->assertArrayNotHasKey( 'description', $first_item );
			$this->assertArrayNotHasKey( '_type', $first_item );
			$this->assertArrayNotHasKey( 'supports', $first_item );

			// Test that we only have exactly the requested fields.
			$this->assertCount( 2, $first_item );
		}
	}

	/**
	 * Test that schema filtering prevents data leakage.
	 */
	public function test_schema_filtering_prevents_data_leakage() {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/payments/offline-methods' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		if ( ! empty( $data ) ) {
			$first_item        = $data[0];
			$schema_properties = array_keys( $this->endpoint->get_collection_schema()['items']['properties'] );

			// Allow framework-added fields like _links.
			$allowed_framework_fields = array( '_links' );
			$allowed_fields           = array_merge( $schema_properties, $allowed_framework_fields );

			// Test that response only contains fields from schema or allowed framework fields.
			foreach ( array_keys( $first_item ) as $field ) {
				$this->assertContains( $field, $allowed_fields, "Field '{$field}' not declared in schema or allowed framework fields" );
			}
		}
	}
}
