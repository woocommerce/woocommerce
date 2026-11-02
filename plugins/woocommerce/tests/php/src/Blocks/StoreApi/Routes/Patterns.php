<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use Automattic\WooCommerce\Blocks\Patterns\PTKPatternsStore;

/**
 * Patterns Controller Tests.
 */
class Patterns extends ControllerTestCase {
	/**
	 * Number of PTK API requests made by the test.
	 *
	 * @var int
	 */
	private $ptk_request_count = 0;

	/**
	 * Set up user for tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->ptk_request_count = 0;

		$user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );

		add_filter( 'pre_http_request', array( $this, 'mock_ptk_response' ), 10, 3 );
	}

	/**
	 * Remove the scoped PTK HTTP mock.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_ptk_response' ), 10 );
		parent::tearDown();
	}

	/**
	 * Test the post endpoint when tracking is not allowed.
	 *
	 * @return void
	 */
	public function test_post_endpoint_when_tracking_is_not_allowed() {
		update_option( 'woocommerce_allow_tracking', 'no' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'POST', '/wc/private/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$patterns = get_option( PTKPatternsStore::OPTION_NAME );
		$this->assertFalse( $patterns );
		$this->assertSame( 0, $this->ptk_request_count );
	}

	/**
	 * Test the post endpoint when tracking is allowed.
	 *
	 * @return void
	 */
	public function test_post_endpoint_when_tracking_is_allowed() {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'POST', '/wc/private/patterns' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( true, $data['success'] );

		$expected_patterns = array(
			array(
				'ID'         => 14870,
				'site_id'    => 174455321,
				'title'      => 'Review: A quote with scattered images',
				'name'       => 'review-a-quote-with-scattered-images',
				'html'       => '<!-- /wp:spacer -->',
				'categories' => array(
					'reviews' => array(
						'slug'  => 'reviews',
						'title' => 'Reviews',
					),
				),
			),
		);

		$this->assertSame( 1, $this->ptk_request_count );
		$this->assertSame( $expected_patterns, get_option( PTKPatternsStore::OPTION_NAME ) );
	}

	/**
	 * Return a deterministic response for PTK API requests.
	 *
	 * @param mixed  $preempt     Whether to preempt the HTTP request.
	 * @param array  $parsed_args HTTP request arguments.
	 * @param string $url         Request URL.
	 * @return mixed
	 */
	public function mock_ptk_response( $preempt, $parsed_args, $url ) {
		if ( 0 !== strpos( $url, PTKClient::PATTERNS_TOOLKIT_URL ) ) {
			return $preempt;
		}

		++$this->ptk_request_count;

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode(
				array(
					array(
						'ID'         => 14870,
						'site_id'    => 174455321,
						'title'      => 'Review: A quote with scattered images',
						'name'       => 'review-a-quote-with-scattered-images',
						'html'       => '<!-- /wp:spacer -->',
						'categories' => array(
							'testimonials' => array(
								'slug'        => 'testimonials',
								'title'       => 'Testimonials',
								'description' => 'Share reviews and feedback about your brand/business.',
							),
						),
					),
				)
			),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}
}
