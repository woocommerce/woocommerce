<?php
/**
 * Options REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\Experiments;

/**
 * WC Tests API Options
 */
class WC_Admin_Tests_API_Experiments extends WC_REST_Unit_Test_Case {

	/** Reuse only the baseline administrator; explicit actors stay per-test. */
	protected const USE_SHARED_ADMINISTRATOR_FIXTURE = true;

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/experiments';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = self::get_shared_administrator_fixture_id();
	}

	/**
	 * Test getting assignment without valid permissions.
	 */
	public function test_get_assignment_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/assignment' );
		$request->set_query_params( array( 'experiment_name' => 'test' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}


	/**
	 * Test getting assignment without valid params.
	 */
	public function test_get_assignment_without_experiment_name() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/assignment' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}
}
