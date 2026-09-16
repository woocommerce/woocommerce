<?php
/**
 * Options REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\Options;

/**
 * WC Tests API Options
 */
class WC_Admin_Tests_API_Options extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/options';

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
	}

	/**
	 * Test getting options without valid permissions.
	 */
	public function test_get_options_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'options' => 'woocommerce_demo_store_notice' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox A logged-in user without WooCommerce admin capabilities cannot read protected WC Admin options.
	 *
	 * @dataProvider protected_options_provider
	 *
	 * @param string $option_name Protected option name.
	 */
	public function test_get_protected_options_without_woocommerce_admin_permission( string $option_name ): void {
		$user = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'options' => $option_name ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), "The {$option_name} option should remain unavailable to limited users." );
	}

	/**
	 * Protected WC Admin options.
	 *
	 * @return array[]
	 */
	public function protected_options_provider(): array {
		return array(
			'Customer Effort Score queue' => array( 'woocommerce_ces_tracks_queue' ),
			'transient notices queue'     => array( 'woocommerce_admin_transient_notices_queue' ),
			'admin install timestamp'     => array( 'woocommerce_admin_install_timestamp' ),
			'remote variant assignment'   => array( 'woocommerce_remote_variant_assignment' ),
		);
	}

	/**
	 * Test updating options without valid permissions.
	 */
	public function test_update_options_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$request->set_body( wp_json_encode( array( 'woocommerce_demo_store_notice' => 'Store notice updated.' ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that options can be retrieved.
	 */
	public function test_get_options() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'options' => 'woocommerce_demo_store_notice' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( get_option( 'woocommerce_demo_store_notice' ), $data['woocommerce_demo_store_notice'] );
	}

	/**
	 * Test that options can be updated.
	 */
	public function test_update_options() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$request->set_body( wp_json_encode( array( 'woocommerce_demo_store_notice' => 'Store notice updated.' ) ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Store notice updated.', get_option( 'woocommerce_demo_store_notice' ) );
	}
}
