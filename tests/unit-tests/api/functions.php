<?php

/**
 * REST API Functions.
 * @package WooCommerce\Tests\API
 * @since 2.6.0
 */
class WC_Tests_API_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_rest_prepare_date_response().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_prepare_date_response() {
		$this->assertEquals( '2016-06-06T06:06:06', wc_rest_prepare_date_response( '2016-06-06 06:06:06' ) );
	}

	/**
	 * Test wc_rest_upload_image_from_url().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_upload_image_from_url() {
		// Only test the error, no need to upload images.
		$this->assertIsWPError( wc_rest_upload_image_from_url( 'thing' ) );
	}

	/**
	 * Test wc_rest_set_uploaded_image_as_attachment().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_set_uploaded_image_as_attachment() {
		$this->assertInternalType( 'int', wc_rest_set_uploaded_image_as_attachment( array( 'file' => '', 'url' => '' ) ) );
	}

	/**
	 * Test wc_rest_validate_reports_request_arg().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_validate_reports_request_arg() {
		$request = new WP_REST_Request( 'GET', '/wc/v1/foo', array(
			'args' => array(
				'date' => array(
					'type'   => 'string',
					'format' => 'date',
				),
			),
		) );

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
		$this->assertEquals( 'https%253A%252F%252Fwoocommerce.com%252F', wc_rest_urlencode_rfc3986( 'https://woocommerce.com/' ) );
	}

	/**
	 * Test wc_rest_check_post_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_post_permissions() {
		$this->isFalse( wc_rest_check_post_permissions( 'shop_order' ) );
	}

	/**
	 * Test wc_rest_check_user_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_user_permissions() {
		$this->isFalse( wc_rest_check_user_permissions() );
	}

	/**
	 * Test wc_rest_check_product_term_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_product_term_permissions() {
		$this->isFalse( wc_rest_check_product_term_permissions( 'product_cat' ) );
	}

	/**
	 * Test wc_rest_check_manager_permissions().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_rest_check_manager_permissions() {
		$this->isFalse( wc_rest_check_manager_permissions( 'reports' ) );
	}
}
