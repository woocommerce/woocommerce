<?php
/**
 * File for the WC_Tests_API_Functions class.
 *
 * @package WooCommerce\Tests\API
 */

/**
 * REST API Functions.
 * @since 2.6.0
 */
class WC_Tests_API_Functions extends WC_Unit_Test_Case {

	/**
	 * @var string path to the WP upload dir.
	 */
	private $upload_dir_path;

	/**
	 * @var string WP upload dir URL.
	 */
	private $upload_dir_url;

	/**
	 * @var string Name of the file used in wc_rest_upload_image_from_url() tests.
	 */
	private $file_name;

	/**
	 * @var string Regex used to match the file name used in wc_rest_upload_image_from_url() tests.
	 */
	private $file_regex;

	/**
	 * Run setup code for unit tests.
	 */
	public function setUp(): void {
		parent::setUp();

		// Callback used by WP_HTTP_TestCase to decide whether to perform HTTP requests or to provide a mocked response.
		$this->http_responder = array( $this, 'mock_http_responses' );

		$upload_dir_info       = wp_upload_dir();
		$this->upload_dir_path = $upload_dir_info['path'];
		$this->upload_dir_url  = $upload_dir_info['url'];
		$this->file_name       = 'Dr1Bczxq4q.png';
		$this->file_regex      = '/Dr1Bczxq4q(?:-[0-9]+)?\.png$/';
	}

	/**
	 * Run tear down code for unit tests.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// remove files created in the wc_rest_upload_image_from_url() tests.
		$file_path = $this->upload_dir_path . '/' . $this->file_name;

		if ( file_exists( $file_path ) ) {
			unlink( $file_path );
		}
	}

	/**
	 * Test wc_rest_prepare_date_response().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_prepare_date_response() {
		$this->assertEquals( '2016-06-06T06:06:06', wc_rest_prepare_date_response( '2016-06-06 06:06:06' ) );
	}

	/**
	 * Test wc_rest_upload_image_from_url() should return error when unable to download image.
	 */
	public function test_wc_rest_upload_image_from_url_should_return_error_when_unable_to_download_image() {
		$expected_error_message = 'Error getting remote image http://somedomain.com/nonexistent-image.png. Error: Not found.';
		$result                 = wc_rest_upload_image_from_url( 'http://somedomain.com/nonexistent-image.png' );

		$this->assertWPError( $result );
		$this->assertEquals( $expected_error_message, $result->get_error_message() );
	}

	/**
	 * Test wc_rest_upload_image_from_url() should return error when invalid image is passed.
	 *
	 * @requires PHP 5.4
	 */
	public function test_wc_rest_upload_image_from_url_should_return_error_when_invalid_image_is_passed() {
		// empty file.
		$wp_version = get_bloginfo( 'version' );
		if ( version_compare( $wp_version, '5.4-alpha', '>=' ) ) {
			$expected_error_message = 'Invalid image: File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini file or by post_max_size being defined as smaller than upload_max_filesize in php.ini.';
		} else {
			$expected_error_message = 'Invalid image: File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.';
		}
		$result = wc_rest_upload_image_from_url( 'http://somedomain.com/invalid-image-1.png' );

		$this->assertWPError( $result );
		$this->assertEquals( $expected_error_message, $result->get_error_message() );

		// unsupported mime type.
		$expected_error_message = version_compare( $wp_version, '5.9-alpha', '>=' ) ? 'Invalid image: Sorry, you are not allowed to upload this file type.' : 'Invalid image: Sorry, this file type is not permitted for security reasons.';
		$result                 = wc_rest_upload_image_from_url( 'http://somedomain.com/invalid-image-2.png' );

		$this->assertWPError( $result );
		$this->assertEquals( $expected_error_message, $result->get_error_message() );
	}

	/**
	 * Test wc_rest_upload_image_from_url() should download image and return an array containing
	 * information about it.
	 *
	 * @requires PHP 5.4
	 */
	public function test_wc_rest_upload_image_from_url_should_download_image_and_return_array() {
		$result = wc_rest_upload_image_from_url( 'http://somedomain.com/' . $this->file_name );

		$this->assertMatchesRegularExpression( $this->file_regex, $result['file'] );
		$this->assertMatchesRegularExpression( $this->file_regex, $result['url'] );
		$this->assertEquals( 'image/png', $result['type'] );
	}

	/**
	 * Test wc_rest_set_uploaded_image_as_attachment().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_set_uploaded_image_as_attachment() {
		$this->assertIsInt(
			wc_rest_set_uploaded_image_as_attachment(
				array(
					'file' => '',
					'url'  => '',
				)
			)
		);
	}

	/**
	 * Test wc_rest_validate_reports_request_arg().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_validate_reports_request_arg() {
		$request = new WP_REST_Request(
			'GET',
			'/wc/v3/foo',
			array(
				'args' => array(
					'date' => array(
						'type'   => 'string',
						'format' => 'date',
					),
				),
			)
		);

		// Success.
		$this->assertTrue( wc_rest_validate_reports_request_arg( '2016-06-06', $request, 'date' ) );

		// Error.
		$error = wc_rest_validate_reports_request_arg( 'foo', $request, 'date' );
		$this->assertEquals( 'The date you provided is invalid.', $error->get_error_message() );
	}

	/**
	 * Test wc_rest_urlencode_rfc3986().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_urlencode_rfc3986() {
		$this->assertEquals( 'https%3A%2F%2Fwoo.com%2F', wc_rest_urlencode_rfc3986( 'https://woo.com/' ) );
	}

	/**
	 * Test wc_rest_check_post_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_post_permissions() {
		$this->assertFalse( wc_rest_check_post_permissions( 'shop_order' ) );
	}

	/**
	 * Test wc_rest_check_user_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_user_permissions() {
		$this->assertFalse( wc_rest_check_user_permissions() );
	}

	/**
	 * Test wc_rest_check_product_term_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_product_term_permissions() {
		$this->assertFalse( wc_rest_check_product_term_permissions( 'product_cat' ) );
	}

	/**
	 * Test wc_rest_check_manager_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_manager_permissions() {
		$this->assertFalse( wc_rest_check_manager_permissions( 'reports' ) );
	}

	/**
	 * Helper method to define mocked HTTP responses using WP_HTTP_TestCase.
	 * Thanks to WP_HTTP_TestCase, it is not necessary to perform a regular request
	 * to an external server which would significantly slow down the tests.
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

		if ( 'http://somedomain.com/nonexistent-image.png' === $url ) {
			$mocked_response = array(
				'response' => array(
					'code'    => 404,
					'message' => 'Not found.',
				),
			);
		} elseif ( 'http://somedomain.com/invalid-image-1.png' === $url ) {
			// empty image.
			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		} elseif ( 'http://somedomain.com/invalid-image-2.png' === $url ) {
			// image with an unsupported mime type.
			// we need to manually copy the file as we are mocking the request. without this an empty file is created.
			self::file_copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/file.txt', $request['filename'] );

			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		} elseif ( 'http://somedomain.com/' . $this->file_name === $url ) {
			// we need to manually copy the file as we are mocking the request. without this an empty file is created.
			self::file_copy( WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png', $request['filename'] );

			$mocked_response = array(
				'response' => array( 'code' => 200 ),
			);
		}

		return $mocked_response;
	}
}
