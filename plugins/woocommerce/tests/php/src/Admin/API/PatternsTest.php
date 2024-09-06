<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;
use WC_REST_Unit_Test_Case;

/**
 * Patterns Controller Tests.
 */
class PatternsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Set up user for tests.
	 */
	public function setUp(): void {
		parent::setUp();

		$user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );
	}

	/**
	 * Test the post endpoint when tracking is not allowed.
	 *
	 * @return void
	 */
	public function test_post_endpoint_when_tracking_is_not_allowed() {
		update_option( 'woocommerce_allow_tracking', 'no' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'POST', '/wc-admin/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test the post endpoint when tracking is allowed.
	 *
	 * @return void
	 */
	public function test_post_endpoint_when_tracking_is_allowed() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'POST', '/wc-admin/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertNotFalse( $patterns );
	}

	/**
	 * Test the get endpoint when tracking is not allowed.
	 *
	 * @return void
	 */
	public function test_get_endpoint_when_tracking_is_not_allowed() {
		update_option( 'woocommerce_allow_tracking', 'no' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc-admin/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}

	/**
	 * Test the get endpoint when tracking is allowed.
	 *
	 * @return void
	 */
	public function test_get_endpoint_when_tracking_is_allowed() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		rest_get_server()->dispatch( new \WP_REST_Request( 'POST', '/wc-admin/patterns' ) );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc-admin/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertNotFalse( $patterns );
	}

	/**
	 * Test the get endpoint when the PTK client fails.
	 *
	 * @return void
	 */
	public function test_get_endpoint_when_ptk_client_fails() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$error = function () {
			return new \WP_Error( 'error', 'An error message' );
		};
		add_filter( 'pre_http_request', $error, 10, 3 );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc-admin/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'patterns_toolkit_api_error', $data['code'] );
		$this->assertEquals( 'Failed to connect with the Patterns Toolkit API: try again later.', $data['message'] );

		$patterns = get_transient( PTKPatternsStore::TRANSIENT_NAME );
		$this->assertFalse( $patterns );
	}
}
