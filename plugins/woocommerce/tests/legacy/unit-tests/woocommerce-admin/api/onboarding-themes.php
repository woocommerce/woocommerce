<?php
/**
 * Onboarding Themes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use \Automattic\WooCommerce\Admin\API\OnboardingThemes;
use \Automattic\WooCommerce\Internal\Admin\Onboarding;

/**
 * WC Tests API Onboarding Themes
 */
class WC_Admin_Tests_API_Onboarding_Themes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-admin/onboarding/themes';

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
	 * Test that installation without permission is unauthorized.
	 */
	public function test_install_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'POST', $this->endpoint . '/install' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that installing a valid theme works.
	 */
	public function test_install_theme() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'theme' => 'storefront',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$themes   = wp_get_themes();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'storefront', $data['slug'] );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertArrayHasKey( 'storefront', $themes );
	}

	/**
	 * Test that installing an invalid theme fails.
	 */
	public function test_install_invalid_theme() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/install' );
		$request->set_query_params(
			array(
				'theme' => 'invalid-theme-name',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_theme', $data['code'] );
	}

	/**
	 * Test that activating a valid theme works.
	 */
	public function test_activate_theme() {
		wp_set_current_user( $this->user );

		$previous_theme = get_stylesheet();
		$request        = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'theme' => 'storefront',
			)
		);
		$response     = $this->server->dispatch( $request );
		$data         = $response->get_data();
		$active_theme = get_stylesheet();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'storefront', $data['slug'] );
		$this->assertEquals( 'success', $data['status'] );
		$this->assertNotEquals( 'storefront', $previous_theme );
		$this->assertEquals( 'storefront', $active_theme );
	}

	/**
	 * Test that activating an invalid theme fails.
	 */
	public function test_activate_invalid_theme() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', $this->endpoint . '/activate' );
		$request->set_query_params(
			array(
				'theme' => 'invalid-theme-name',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_invalid_theme', $data['code'] );
	}
}
