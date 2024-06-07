<?php
/**
 * Onboarding Themes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\OnboardingThemes;
use Automattic\WooCommerce\Internal\Admin\Onboarding;

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

		$this->assertEquals( 'woocommerce_rest_theme_install', $data['code'] );
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

	/**
	 * Test that get recommended themes returns the correct data.
	 */
	public function test_get_recommended_themes() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/recommended' );

		// Mock the get_option() function to return 'yes' for marketplace suggestions.
		// This is done to test the marketplace suggestions scenario.
		add_filter(
			'pre_option_woocommerce_show_marketplace_suggestions',
			function () {
				return 'yes';
			}
		);

		$response = $this->server->dispatch( $request );
		$result   = $response->get_data();

		// Check that the response is an array.
		$this->assertIsArray( $result );

		// Check that the 'themes' key is present in the response.
		$this->assertArrayHasKey( 'themes', $result );

		// Check that the '_links' key is present in the response.
		$this->assertArrayHasKey( '_links', $result );

		// Check that the 'themes' array contains theme data.
		$themes = $result['themes'];
		$this->assertIsArray( $themes );
		$this->assertGreaterThan( 0, count( $themes ) );

		// Check that each theme has the expected keys.
		foreach ( $themes as $theme ) {
				$this->assertArrayHasKey( 'name', $theme );
				$this->assertArrayHasKey( 'price', $theme );
				$this->assertArrayHasKey( 'color_palettes', $theme );
				$this->assertArrayHasKey( 'slug', $theme );
				$this->assertArrayHasKey( 'is_active', $theme );
				$this->assertArrayHasKey( 'thumbnail_url', $theme );
				$this->assertArrayHasKey( 'link_url', $theme );
		}

		// Check that the '_links' array contains 'browse_all'.
		$links = $result['_links'];
		$this->assertArrayHasKey( 'browse_all', $links );

		// Check that the 'browse_all' link contains the 'href' key.
		$browse_all_link = $links['browse_all'];
		$this->assertArrayHasKey( 'href', $browse_all_link );
	}

	/**
	 * Test that get recommended themes returns the correct data when marketplace suggestions are disabled.
	 */
	public function test_get_recommended_themes_when_marketplace_suggestions_disabled() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/recommended' );

		// Mock the get_option() function to return 'no' for marketplace suggestions.
		add_filter(
			'pre_option_woocommerce_show_marketplace_suggestions',
			function () {
				return 'no';
			}
		);

		$response = $this->server->dispatch( $request );
		$result   = $response->get_data();

		// Check that the response is an array.
		$this->assertIsArray( $result );

		// Check that the 'themes' key is present in the response.
		$this->assertArrayHasKey( 'themes', $result );

		// Check that the '_links' key is present in the response.
		$this->assertArrayHasKey( '_links', $result );

		// Check that the 'themes' array is empty.
		$themes = $result['themes'];
		$this->assertIsArray( $themes );
		$this->assertEquals( 0, count( $themes ) );

		// Check that the '_links' array contains 'browse_all'.
		$links = $result['_links'];
		$this->assertArrayHasKey( 'browse_all', $links );

		// Check that the 'browse_all' link contains the 'href' key.
		$browse_all_link = $links['browse_all'];
		$this->assertArrayHasKey( 'href', $browse_all_link );
	}

	/**
	 * Test that get recommended themes returns the correct data with filter applied and marketplace suggestions disabled.
	 */
	public function test_get_recommended_themes_with_filter_applied_when_marketplace_suggestions_disabled() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/recommended' );

		$request->set_query_params(
			array(
				'industry' => 'example-industry',
				'currency' => 'EUR',
			)
		);

		// Mock the get_option() function to return 'yes' for marketplace suggestions.
		// This is done to test the marketplace suggestions scenario.
		add_filter(
			'pre_option_woocommerce_show_marketplace_suggestions',
			function () {
				return 'no';
			}
		);

		// Mock the apply_filters() function to capture the response.
		add_filter(
			'__experimental_woocommerce_rest_get_recommended_themes',
			function ( $result, $industry, $currency ) {
				$result['industry'] = $industry;
				$result['currency'] = $currency;
				$result['themes']   = array();
				return $result;
			},
			10,
			3,
		);

		$response = $this->server->dispatch( $request );
		$result   = $response->get_data();

		$this->assert_filter_applied_results( $result );
	}


	/**
	 * Test that get recommended themes returns the correct data with filter applied and marketplace suggestions enabled.
	 */
	public function test_get_recommended_themes_with_filter_applied_when_marketplace_suggestions_enabled() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint . '/recommended' );

		$request->set_query_params(
			array(
				'industry' => 'example-industry',
				'currency' => 'EUR',
			)
		);

		// Mock the get_option() function to return 'yes' for marketplace suggestions.
		// This is done to test the marketplace suggestions scenario.
		add_filter(
			'pre_option_woocommerce_show_marketplace_suggestions',
			function () {
				return 'yes';
			}
		);

		// Mock the apply_filters() function to capture the response.
		add_filter(
			'__experimental_woocommerce_rest_get_recommended_themes',
			function ( $result, $industry, $currency ) {
				$result['industry'] = $industry;
				$result['currency'] = $currency;
				$result['themes']   = array();
				return $result;
			},
			10,
			3,
		);

		$response = $this->server->dispatch( $request );
		$result   = $response->get_data();

		$this->assert_filter_applied_results( $result );
	}

	/**
	 * Check that get recommended themes returns the correct data with filter applied.
	 *
	 * @param array $result The result of API call.
	 */
	private function assert_filter_applied_results( $result ) {
		// Check that the response is an array.
		$this->assertIsArray( $result );

		// Check that the 'themes' key is present in the response.
		$this->assertArrayHasKey( 'themes', $result );

		// Check that the '_links' key is present in the response.
		$this->assertArrayHasKey( '_links', $result );

		// Check that the 'themes' array is empty.
		$themes = $result['themes'];
		$this->assertIsArray( $themes );
		$this->assertEquals( 0, count( $themes ) );

		// Check that the 'industry' and 'currency' keys are present in the response.
		$this->assertArrayHasKey( 'industry', $result );
		$this->assertEquals( 'example-industry', $result['industry'] );

		$this->assertArrayHasKey( 'currency', $result );
		$this->assertEquals( 'EUR', $result['currency'] );

		// Check that the '_links' array contains 'browse_all'.
		$links = $result['_links'];
		$this->assertArrayHasKey( 'browse_all', $links );

		// Check that the 'browse_all' link contains the 'href' key.
		$browse_all_link = $links['browse_all'];
		$this->assertArrayHasKey( 'href', $browse_all_link );
	}
}
