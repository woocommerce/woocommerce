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
 * MarketingCampaigns API controller test.
 *
 * @class MarketingCampaignsTest.
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

		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Request to install-async endpoint.
	 *
	 * @param array $plugins a list of plugins to install.
	 *
	 * @return mixed
	 */
	private function request( $plugins ) {
		$request = new WP_REST_Request( 'POST', self::ENDPOINT . '/install-async' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'plugins' => $plugins,
				)
			)
		);
		$response = $this->server->dispatch( $request );
		return $response->get_data();
	}

	/**
	 * Request to schedulled-installs endpoint.
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
		$data = $this->request( array( 'test' ) );
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
		$data      = $this->request( array( 'test' ) );
		$action_id = $data['job_id'];

		// actino scheduler might take time to kick off.
		// not ideal, but this is only workaround I've found so far.
		sleep(1);
		$data = $this->get( $action_id );
		$this->assertIsArray( $data );
		$this->assertEquals( $action_id, $data['job_id'] );
		$this->assertArrayHasKey( 'plugins', $data );
		$this->assertArrayHasKey( 'test', $data['plugins'] );
		$this->assertArrayHasKey( 'errors', $data['plugins']['test'] );
		$this->assertCount( 1, $data['plugins']['test']['errors'] );
	}
}
