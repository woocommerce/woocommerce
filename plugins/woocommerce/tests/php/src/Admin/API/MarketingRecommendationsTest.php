<?php
/**
 * Test the API controller class that handles the marketing recommendations REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Features\MarketingRecommendations\Init;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * MarketingRecommendations API controller test.
 *
 * @class MarketingRecommendationsTest.
 */
class MarketingRecommendationsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/marketing/recommendations';

	/**
	 * Response mock
	 *
	 * @var response.
	 */
	private $response_mock_ref;

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

		// Clear any existing cache first.
		Init::delete_specs_transient();

		// Mock the response from woocommerce.com API.
		$this->response_mock_ref = function( $preempt, $parsed_args, $url ) {
			if ( str_contains( $url, 'https://woocommerce.com/wp-json/wccom/marketing-tab/1.3/recommendations.json' ) ) {
				return array(
					'success' => true,
					'body'    => wp_json_encode(
						array(
							array(
								'title'          => 'Example Marketing Channel',
								'description'    => 'List your products and create ads, etc.',
								'url'            => 'https://woo.com/products/example-channel',
								'direct_install' => true,
								'icon'           => 'https://woo.com/example.svg',
								'product'        => 'example-channel',
								'plugin'         => 'example-channel/example-channel.php',
								'categories'     => array( Init::MARKETING_EXTENSION_CATEGORY_SLUG ),
								'subcategories'  => array(
									array(
										'slug' => Init::MARKETING_CHANNEL_SUBCATEGORY_SLUG,
										'name' => 'Sales channels',
									),
								),
								'tags'           => array(),
							),
							array(
								'title'          => 'Example Marketing Extension',
								'description'    => 'Automate your customer communications, etc.',
								'url'            => 'https://woo.com/products/example-marketing-extension',
								'direct_install' => true,
								'icon'           => 'https://woo.com/example-marketing-extension.svg',
								'product'        => 'example-marketing-extension',
								'plugin'         => 'example-marketing-extension/example-marketing-extension.php',
								'categories'     => array( Init::MARKETING_EXTENSION_CATEGORY_SLUG ),
								'subcategories'  => array(
									array(
										'slug' => 'email',
										'name' => 'Email',
									),
								),
								'tags'           => array(),
							),
							array(
								'title'          => 'Example NON Marketing Extension',
								'description'    => 'Handle coupons, etc.',
								'url'            => 'https://woo.com/products/example-random-extension',
								'direct_install' => true,
								'icon'           => 'https://woo.com/example-random-extension.svg',
								'product'        => 'example-random-extension',
								'plugin'         => 'example-random-extension/example-random-extension.php',
								'categories'     => array( 'coupons' ),
								'subcategories'  => array(),
								'tags'           => array(),
							),
						)
					),
				);
			}

			return $preempt;
		};

		// Make a new request -- this should populate the cache with the fake data.
		add_filter( 'pre_http_request', $this->response_mock_ref, 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', $this->response_mock_ref );
	}

	/**
	 * Tests that the marketing channel recommendations are returned by the endpoint.
	 */
	public function test_returns_recommended_marketing_channels() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_query_params( array( 'category' => 'channels' ) );

		// $data should contain mocked data from `setUp` method above.
		$data = rest_get_server()->dispatch( $request )->get_data();

		// Confirm the current data returns expected title.
		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Channel', $data[0]['title'] );

		// Remove filter to test that a new request should return the cached data.
		remove_filter( 'pre_http_request', $this->response_mock_ref );

		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Channel', $data[0]['title'] );
	}

	/**
	 * Tests that the marketing extension recommendations are returned by the endpoint.
	 */
	public function test_returns_recommended_marketing_extensions() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_query_params( array( 'category' => 'extensions' ) );

		// $data should contain mocked data from `setUp` method above.
		$data = rest_get_server()->dispatch( $request )->get_data();

		// Confirm the current data returns expected title.
		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Extension', $data[0]['title'] );

		// Remove filter to test that a new request should return the cached data.
		remove_filter( 'pre_http_request', $this->response_mock_ref );

		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Extension', $data[0]['title'] );
	}

	/**
	 * Tests that the endpoint returns an error if the provided category is invalid.
	 */
	public function test_returns_error_if_invalid_category_provided() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_query_params( array( 'category' => 'test-non-existing-invalid-category' ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}
}
