<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\OrderFulfillmentsRestController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Controller as FulfillmentsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema\FulfillmentSchema;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Fulfillments Providers Controller test class
 */
class ProvidersTest extends WC_REST_Unit_Test_Case {

	/**
	 * Controller instance
	 *
	 * @var FulfillmentsController
	 */
	private FulfillmentsController $controller;

	/**
	 * Admin user for tests
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Shop manager user for tests
	 *
	 * @var int
	 */
	private int $shop_manager_user_id;

	/**
	 * Customer user for tests
	 *
	 * @var int
	 */
	private int $customer_user_id;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new FulfillmentsController();
		$this->controller->init( new FulfillmentSchema(), new OrderFulfillmentsRestController() );
		$this->controller->register_routes();

		$this->admin_user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->shop_manager_user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);

		$this->customer_user_id = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);
	}

	/**
	 * Teardown test environment
	 */
	public function tearDown(): void {
		// Delete the created users.
		wp_delete_user( $this->admin_user_id );
		wp_delete_user( $this->shop_manager_user_id );
		wp_delete_user( $this->customer_user_id );

		parent::tearDown();
	}

	/**
	 * Test route registration
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wc/v4/fulfillments/providers', $routes );
	}

	/**
	 * Test get_providers endpoint success
	 */
	public function test_get_providers_success() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test get_providers contains expected providers
	 */
	public function test_get_providers_contains_expected_providers() {
		wp_set_current_user( $this->admin_user_id );

		// Add a test provider using the filter.
		$test_provider = function () {
			return array( 'TestProvider' );
		};
		add_filter( 'woocommerce_fulfillment_shipping_providers', $test_provider );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Remove the filter.
		remove_filter( 'woocommerce_fulfillment_shipping_providers', $test_provider );

		$this->assertIsArray( $data );
		// Since we added a non-existent class, it should be empty.
		$this->assertEmpty( $data );
	}

	/**
	 * Test permission check - admin user
	 */
	public function test_permission_check_admin() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - shop manager user
	 */
	public function test_permission_check_shop_manager() {
		wp_set_current_user( $this->shop_manager_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - customer user
	 */
	public function test_permission_check_customer() {
		wp_set_current_user( $this->customer_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test permission check - unauthenticated user
	 */
	public function test_permission_check_unauthenticated() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test get_providers with feature disabled
	 */
	public function test_get_providers_with_feature_disabled() {
		// Disable the fulfillments feature.
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );

		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );

		// Re-enable the fulfillments feature.
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );

		// The route should still exist and be accessible if the controller is already registered.
		// This test verifies the route exists even when feature is disabled.
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test response format validation
	 */
	public function test_response_format_validation() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/providers' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		// Each provider should have the expected structure if any exist.
		foreach ( $data as $key => $provider ) {
			$this->assertIsString( $key );
			$this->assertIsArray( $provider );
			$this->assertArrayHasKey( 'label', $provider );
			$this->assertArrayHasKey( 'icon', $provider );
			$this->assertArrayHasKey( 'value', $provider );
			$this->assertArrayHasKey( 'url', $provider );
		}
	}
}
