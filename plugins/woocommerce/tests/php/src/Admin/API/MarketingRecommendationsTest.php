<?php
/**
 * Test the API controller class that handles the marketing recommendations REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Internal\Admin\Marketing\MarketingSpecs;
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

		set_transient(
			MarketingSpecs::RECOMMENDED_PLUGINS_TRANSIENT,
			[
				[
					'title'          => 'Example Marketing Channel',
					'description'    => 'List your products and create ads, etc.',
					'url'            => 'https://woo.com/products/example-channel',
					'direct_install' => true,
					'icon'           => 'https://woo.com/example.svg',
					'product'        => 'example-channel',
					'plugin'         => 'example-channel/example-channel.php',
					'categories'     => [ MarketingSpecs::MARKETING_EXTENSION_CATEGORY_SLUG ],
					'subcategories'  => [
						[
							'slug' => MarketingSpecs::MARKETING_CHANNEL_SUBCATEGORY_SLUG,
							'name' => 'Sales channels',
						],
					],
					'tags'           => [],
				],
				[
					'title'          => 'Example Marketing Extension',
					'description'    => 'Automate your customer communications, etc.',
					'url'            => 'https://woo.com/products/example-marketing-extension',
					'direct_install' => true,
					'icon'           => 'https://woo.com/example-marketing-extension.svg',
					'product'        => 'example-marketing-extension',
					'plugin'         => 'example-marketing-extension/example-marketing-extension.php',
					'categories'     => [ MarketingSpecs::MARKETING_EXTENSION_CATEGORY_SLUG ],
					'subcategories'  => [
						[
							'slug' => 'email',
							'name' => 'Email',
						],
					],
					'tags'           => [],
				],
				[
					'title'          => 'Example NON Marketing Extension',
					'description'    => 'Handle coupons, etc.',
					'url'            => 'https://woo.com/products/example-random-extension',
					'direct_install' => true,
					'icon'           => 'https://woo.com/example-random-extension.svg',
					'product'        => 'example-random-extension',
					'plugin'         => 'example-random-extension/example-random-extension.php',
					'categories'     => [ 'coupons' ],
					'subcategories'  => [],
					'tags'           => [],
				],
			]
		);
	}

	/**
	 * Tests that the marketing channel recommendations are returned by the endpoint.
	 */
	public function test_returns_recommended_marketing_channels() {
		$endpoint = self::ENDPOINT;
		$request  = new WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params( [ 'category' => 'channels' ] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Channel', $data[0]['title'] );
		$this->assertEquals( 'example-channel', $data[0]['product'] );
	}

	/**
	 * Tests that the marketing extension recommendations are returned by the endpoint.
	 */
	public function test_returns_recommended_marketing_extensions() {
		$endpoint = self::ENDPOINT;
		$request  = new WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params( [ 'category' => 'extensions' ] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertEquals( 'Example Marketing Extension', $data[0]['title'] );
		$this->assertEquals( 'example-marketing-extension', $data[0]['product'] );
	}

	/**
	 * Tests that the endpoint returns an error if the provided category is invalid.
	 */
	public function test_returns_error_if_invalid_category_provided() {
		$endpoint = self::ENDPOINT;
		$request  = new WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params( [ 'category' => 'test-non-existing-invalid-category' ] );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

}
