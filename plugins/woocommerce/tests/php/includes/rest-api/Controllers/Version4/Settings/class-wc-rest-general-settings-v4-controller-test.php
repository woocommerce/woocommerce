<?php
/**
 * General Settings V4 controller unit tests.
 *
 * @package WooCommerce\RestApi\UnitTests
 * @since   4.0.0
 */

declare(strict_types=1);

/**
 * General Settings V4 controller unit tests.
 *
 * @package WooCommerce\RestApi\UnitTests
 * @since   4.0.0
 */
class WC_REST_General_Settings_V4_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * @var callable
	 */
	private $feature_filter;
	/**
	 * @var string|false
	 */
	private $prev_default_country;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		// Set up the feature flag before parent::setUp() to ensure the feature is enabled.
		$this->feature_filter = function ( $features ) {
			$features[] = 'rest-api-v4';
			return $features;
		};

		add_filter( 'woocommerce_admin_features', $this->feature_filter );

		parent::setUp();

		// This is to reset the country after the test.
		$this->prev_default_country = get_option( 'woocommerce_default_country' );

		// Create a user with permissions.
		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'shop_manager',
			)
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( isset( $this->feature_filter ) ) {
			remove_filter( 'woocommerce_admin_features', $this->feature_filter );
		}
		if ( isset( $this->prev_default_country ) ) {
			update_option( 'woocommerce_default_country', $this->prev_default_country );
		}
		parent::tearDown();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v4/settings/general', $routes );
	}

	/**
	 * Test getting general settings.
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user_id );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/general' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'general', $data['id'] );
		$this->assertArrayHasKey( 'groups', $data );
	}

	/**
	 * Test updating general settings.
	 */
	public function test_update_item() {
		wp_set_current_user( $this->user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/general' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'woocommerce_default_country' => 'US:CA',
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'US:CA', get_option( 'woocommerce_default_country' ) );
	}

	/**
	 * Test getting general settings without permission.
	 */
	public function test_get_item_without_permission() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'GET', '/wc/v4/settings/general' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating general settings without permission.
	 */
	public function test_update_item_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'PUT', '/wc/v4/settings/general' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'woocommerce_default_country' => 'US:CA',
				)
			)
		);
		$response = $this->server->dispatch( $request );
		$response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}
}
