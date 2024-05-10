<?php

namespace Automattic\WooCommerce\Tests\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\Patterns\PTKClient;
use WP_Error;

/**
 * Unit tests for the Patterns Toolkit class.
 */
class PatternsToolkitClientTest extends \WP_UnitTestCase {
	/**
	 * The client instance.
	 *
	 * @var PTKClient $client
	 */
	private $client;

	/**
	 * Initialize the client instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->client = new PTKClient();
	}

	/**
	 * Test fetch_patterns returns an error when the request fails.
	 */
	public function test_fetch_patterns_returns_an_error_when_the_request_fails() {
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'error', 'Request failed.' );
			}
		);

		$response = $this->client->fetch_patterns();
		$this->assertErrorResponse( $response, 'Failed to connect with the Patterns Toolkit API: try again later.' );

		remove_filter( 'pre_http_request', array( $this, 'return_wp_error' ) );
	}

	/**
	 * Test fetch_patterns returns an error when the request is unsuccessful.
	 */
	public function test_fetch_patterns_returns_an_error_when_the_request_is_unsuccessful() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => '{"code":"rest_error","message":"The request failed.","data":{"status":500}}',
					'response' => array(
						'code' => 500,
					),
				);
			}
		);

		$response = $this->client->fetch_patterns();
		$this->assertErrorResponse( $response, 'Failed to connect with the Patterns Toolkit API: try again later.' );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Test fetch_patterns returns an error when the response body is empty
	 */
	public function test_fetch_patterns_returns_an_error_when_the_response_body_is_empty() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => '',
					'response' => array(
						'code' => 200,
					),
				);
			},
		);

		$response = $this->client->fetch_patterns();
		$this->assertErrorResponse( $response, 'Empty response received from the Patterns Toolkit API.' );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Test fetch_patterns returns an error when the response body is not an array
	 */
	public function test_fetch_patterns_returns_an_error_when_the_response_body_is_not_an_array() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => 'X',
					'response' => array(
						'code' => 200,
					),
				);
			},
		);

		$response = $this->client->fetch_patterns( array( 'categories' => array( 'pepe' ) ) );
		$this->assertErrorResponse( $response, 'Wrong response received from the Patterns Toolkit API: try again later.' );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Test fetch_patterns returns an array of patterns when the request is successful.
	 */
	public function test_fetch_patterns_returns_an_array_of_patterns_when_the_request_is_successful() {
		add_filter(
			'pre_http_request',
			function () {
				return array(
					'body'     => '[
                        {
                            "ID": 14870,
                            "site_id": 174455321,
                            "title": "Review: A quote with scattered images",
                            "name": "review-a-quote-with-scattered-images",
                            "html": "<!-- /wp:spacer -->",
                            "categories": {
                                "testimonials": {
                                    "slug": "testimonials",
                                    "title": "Testimonials",
                                    "description": "Share reviews and feedback about your brand/business."
                                }
                            }
                        }
                    ]',
					'response' => array(
						'code' => 200,
					),
				);
			},
		);

		$response = $this->client->fetch_patterns( array( 'categories' => array( 'pepe' ) ) );
		$this->assertIsArray( $response );
		$this->assertCount( 1, $response );

		remove_filter( 'pre_http_request', array( $this, 'return_request_failed' ) );
	}

	/**
	 * Asserts that the response is an error with the expected error code and message.
	 *
	 * @param array|WP_Error $response The response to assert.
	 * @param string         $expected_error_message The expected error message.
	 * @return void
	 */
	public function assertErrorResponse( $response, $expected_error_message ) {
		$this->assertInstanceOf( WP_Error::class, $response );

		$error_code = $response->get_error_code();
		$this->assertEquals( 'patterns_toolkit_api_error', $error_code );

		$error_message = $response->get_error_message();
		$this->assertEquals( $expected_error_message, $error_message );
	}
}
