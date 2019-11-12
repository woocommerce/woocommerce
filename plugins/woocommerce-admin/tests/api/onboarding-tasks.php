<?php
/**
 * Onboarding Tasks REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingTasks;

/**
 * WC Tests API Onboarding Tasks
 */
class WC_Tests_API_Onboarding_Tasks extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/tasks';

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
	 * Test that sample product data is imported.
	 */
	public function test_import_sample_products() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/import_sample_products' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'failed', $data );
		$this->assertArrayHasKey( 'imported', $data );
		$this->assertArrayHasKey( 'skipped', $data );
		$this->assertArrayHasKey( 'updated', $data );
	}

	/**
	 * Test that Tasks data is returned by the endpoint.
	 */
	public function test_create_homepage() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', $this->endpoint . '/create_homepage' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertEquals( get_option( 'woocommerce_onboarding_homepage_post_id' ), $data['post_id'] );
		$this->assertEquals( htmlspecialchars_decode( get_edit_post_link( get_option( 'woocommerce_onboarding_homepage_post_id' ) ) ), $data['edit_post_link'] );
	}
}
