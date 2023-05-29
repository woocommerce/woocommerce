<?php
/**
 * Test the API controller class that handles the onboarding plugins REST endpoints.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * OnboardingPlugins API controller test.
 *
 * @class OnboardingPluginsTest.
 */
class OnboardingPluginsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/onboarding/plugins';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->useAdmin();
	}

	/**
	 * Use a user with administrator role.
	 *
	 * @return void
	 */
	public function useAdmin() {
		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Use a user without any permissions.
	 *
	 * @return void
	 */
	public function useUserWithoutPluginsPermission() {
		$this->user = $this->factory->user->create();
		wp_set_current_user( $this->user );
	}

	/**
	 * Request to install-async endpoint.
	 *
	 * @param string $endpoint Request endpoint.
	 * @param string $body Request body.
	 *
	 * @return mixed
	 */
	private function request( $endpoint, $body ) {
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . $endpoint );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $body );
		$response = $this->server->dispatch( $request );

		return $response;
	}

	/**
	 * Request to scheduled-installs endpoint.
	 *
	 * @param string $job_id job id.
	 *
	 * @return mixed
	 */
	private function get( $job_id ) {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/scheduled-installs/' . $job_id );

		return $this->server->dispatch( $request )->get_data();
	}

	/**
	 * Test to confirm install-async response format.
	 *
	 * @return void
	 */
	public function test_response_format() {
		$data = $this->request(
			'/install-and-activate-async',
			wp_json_encode(
				array(
					'plugins' => array( 'test' ),
				)
			)
		)->get_data();
		$this->assertArrayHasKey( 'job_id', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'plugins', $data );
		$this->assertTrue( isset( $data['plugins']['test'] ) );
	}

	/**
	 * Test to confirm it queues an action scheduler job.
	 *
	 * @return void
	 */
	public function test_it_queues_action() {
		$this->markTestSkipped( 'Skipping it for now until we find a better way of testing it.' );
		$data      = $this->request(
			'/install-async',
			wp_json_encode(
				array(
					'plugins' => array( 'test' ),
				)
			)
		)->get_data();
		$action_id = $data['job_id'];
		$data      = $this->get( $action_id );
		$this->assertIsArray( $data );
		$this->assertEquals( $action_id, $data['job_id'] );
	}

	/**
	 * Test it returns 404 when an unknown job id is given.
	 *
	 * @return void
	 */
	public function test_it_returns_404_with_unknown_job_id() {
		$request  = new WP_REST_Request( 'GET', self::ENDPOINT . '/scheduled-installs/646e6a35121601' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test permissions.
	 *
	 * @return void
	 */
	public function test_permissions() {
		$this->useUserWithoutPluginsPermission();
		foreach ( array( '/install-and-activate', '/install-and-activate-async' ) as $endpoint ) {
			$response = $this->request(
				$endpoint,
				wp_json_encode(
					array(
						'plugins' => array( 'test' ),
					)
				)
			);
			$this->assertEquals( 403, $response->get_status() );
		}
	}
}
