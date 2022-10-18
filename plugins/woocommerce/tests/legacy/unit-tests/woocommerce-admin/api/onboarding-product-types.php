<?php
/**
 * Onboarding Product Types REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\OnboardingProductTypes;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProducts;

/**
 * WC Tests API Onboarding Product Types
 */
class WC_Admin_Tests_API_Onboarding_Product_Types extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/product-types';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Test that the allowed product types data is returned by the endpoint.
	 */
	public function test_get_product_types() {

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$properties = OnboardingProducts::get_allowed_product_types();
		foreach ( $properties as $key => $property ) {
			$this->assertArrayHasKey( $key, $data );
		}
	}

	/**
	 * Test subscriptions inclusion only for US stores.
	 */
	public function test_subscriptions_inclusion() {
		// The store location is: US.
		update_option( 'woocommerce_default_country', 'US' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( array_key_exists( 'product', $data['subscriptions'] ) );

		// Now the store location is: France.
		update_option( 'woocommerce_default_country', 'FR' );

		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( array_key_exists( 'product', $data['subscriptions'] ) );

		// Now the store location is: Uruguay.
		update_option( 'woocommerce_default_country', 'UY' );

		$request_another_country  = new WP_REST_Request( 'GET', $this->endpoint );
		$response_another_country = $this->server->dispatch( $request_another_country );
		$data_another_country     = $response_another_country->get_data();

		$this->assertTrue( array_key_exists( 'product', $data_another_country['subscriptions'] ) );
	}
}
