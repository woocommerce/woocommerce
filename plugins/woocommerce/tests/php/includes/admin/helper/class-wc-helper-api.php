<?php

/**
 * Test class for WC_Helper_API.
 */
class WC_Tests_Helper_API extends WC_Unit_Test_Case {

	/**
	 * Set up mock responses for all API calls.
	 */
	public function setUp(): void {
		parent::setUp();

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );
	}

	/**
	 * Test that the url method returns the correct Woo.com path.
	 */
	public function test_api_url() {
		$url = WC_Helper_API::url( '/test-path' );
		$this->assertEquals( 'https://woocommerce.com/wp-json/helper/1.0/test-path', $url );
	}

	/**
	 * Test a GET request through the WC_Helper_API.
	 */
	public function test_get_request() {
		$request = WC_Helper_API::get(
			'test-get'
		);

		$this->assertEquals( '200', $request['response']['code'] );
	}

	/**
	 * Test a POST request through the WC_Helper_API.
	 */
	public function test_post_request() {
		$request = WC_Helper_API::post(
			'test-post'
		);

		$this->assertEquals( '200', $request['response']['code'] );
	}

	/**
	 * Test a PUT request through the WC_Helper_API.
	 */
	public function test_put_request() {
		$request = WC_Helper_API::put(
			'test-put'
		);

		$this->assertEquals( '200', $request['response']['code'] );
	}

	/**
	 * Provides a mocked response for various paths and request methods.
	 *
	 * This function is called by WP_HTTP_TestCase::http_request_listner().
	 *
	 * @param array  $request Request arguments.
	 * @param string $url URL of the request.
	 *
	 * @return array|false mocked response or false to let WP perform a regular request.
	 */
	protected function mock_http_responses( $request, $url ) {
		$mocked_response = false;

		if ( 'GET' === $request['method'] && WC_Helper_API::url( 'test-get' ) === $url ) {
			$mocked_response = array(
				'body'     => 'Mocked response',
				'response' => array( 'code' => 200 ),
			);
		}

		if ( 'POST' === $request['method'] && WC_Helper_API::url( 'test-post' ) === $url ) {
			$mocked_response = array(
				'body'     => 'Mocked response',
				'response' => array( 'code' => 200 ),
			);
		}

		if ( 'PUT' === $request['method'] && WC_Helper_API::url( 'test-put' ) === $url ) {
			$mocked_response = array(
				'body'     => 'Mocked response',
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}

}
