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
	protected $endpoint = '/wc-admin/v1/onboarding/tasks';

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
}
