<?php

namespace Automattic\WooCommerce\Tests\TransientFiles;

use Automattic\WooCommerce\TransientFiles\TransientFilesEngine;

/**
 * Tests for the TransientFilesRestController closs.
 */
class TransientFilesRestControllerTest extends TransientFilesTestBase {

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wp_upload_dir' => fn() => array( 'basedir' => self::$base_transient_files_dir ),
				'current_time'  => fn( $type, $gmt ) =>
					$gmt && 'timestamp' === $type ? strtotime( '2222-11-01 12:34:00' ) :
					( $gmt && 'mysql' === $type ? '2222-11-01 12:34:00' : current_time( $type, $gmt ) ),
			)
		);
	}

	/**
	 * @testdox The create file endpoint returns "unauthorized" error if the user doesn't have the "create_transient_file" permission.
	 */
	public function test_create_file_request_returns_unauthorized_error_if_user_doesnt_have_create_permission(): void {
		$response = $this->do_rest_post_request( 'files/transient/render', array( 'template_name' => 'foo' ) );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_cannot_create',
			'message' => 'Sorry, you cannot create resources.',
			'data'    => array(
				'status' => 401,
			),
		);

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the input doesn't include a template name.
	 */
	public function test_create_file_request_resturns_error_if_no_template_name_specified(): void {
		$response = $this->do_rest_post_request( 'files/transient/render' );

		$expected_response_body = array(
			'code'    => 'rest_missing_callback_param',
			'message' => 'Missing parameter(s): template_name',
			'data'    => array(
				'status' => 400,
				'params' => array( 'template_name' ),
			),
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the input doesn't include an expiration date or interval.
	 */
	public function test_create_file_request_returns_error_if_no_expiration_specified(): void {
		$this->test_create_file_request_returns_error_if_invalid_parameters_are_supplied(
			array( 'template_name' => 'simple' ),
			'The metadata array must have either an expiration_date key or an expiration_seconds key'
		);
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the specified template doesn't exist.
	 */
	public function test_create_file_request_returns_error_if_template_is_not_found(): void {
		$this->test_create_file_request_returns_error_if_invalid_parameters_are_supplied(
			array(
				'template_name'      => 'INVALID',
				'expiration_seconds' => 100,
			),
			'Template not found: INVALID'
		);
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the supplied expiration date is wrongly formatted.
	 */
	public function test_create_file_request_returns_error_if_expiration_date_is_invalid(): void {
		$this->test_create_file_request_returns_error_if_invalid_parameters_are_supplied(
			array(
				'template_name'   => 'simple',
				'expiration_date' => 'INVALID',
			),
			'INVALID is not a valid date, expected format: year-month-day hour:minute:second'
		);
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the supplied expiration date results in a data below the minimum allowed.
	 */
	public function test_create_file_request_returns_error_if_expiration_date_is_too_soon(): void {
		$this->test_create_file_request_returns_error_if_invalid_parameters_are_supplied(
			array(
				'template_name'   => 'simple',
				'expiration_date' => '2222-11-01 12:34:00',
			),
			'expiration_date must be higher than the current time plus 60 seconds, got 2222-11-01 12:34:00'
		);
	}

	/**
	 * @testdox The create file endpoint returns "invalid parameter" error if the supplied expiration interval results in a data below the minimum allowed.
	 */
	public function test_create_file_request_returns_error_if_expiration_seconds_is_too_low(): void {
		$this->test_create_file_request_returns_error_if_invalid_parameters_are_supplied(
			array(
				'template_name'      => 'simple',
				'expiration_seconds' => 34,
			),
			'expiration_seconds must be a number and have a minimum value of 60, got 34'
		);
	}

	/**
	 * Base method the testing invalid parameters for the file creation endpoint.
	 *
	 * @param array  $parameters The parameters to supply to the endpoint.
	 * @param string $expected_error_message The expected error message to receive.
	 */
	private function test_create_file_request_returns_error_if_invalid_parameters_are_supplied( array $parameters, string $expected_error_message ): void {
		$this->mock_user_capability( 'create_transient_file' );
		$response = $this->do_rest_post_request( 'files/transient/render', $parameters );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_invalid_arguments',
			'message' => $expected_error_message,
			'data'    => array(
				'status' => 400,
			),
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * Auxiliary method to mock a given capability as being granted to the current user.
	 *
	 * @param string $capability_name The capability to grant.
	 */
	private function mock_user_capability( string $capability_name ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'current_user_can' => fn( $capability ) => $capability === $capability_name || current_user_can( $capability ),
			)
		);
	}

	/**
	 * @testdox The create file endpoint successfully creates the file given correct input parameters.
	 *
	 * @testWith [true, "expiration_date", "2222-11-02 12:34:00"]
	 *           [true, "expiration_seconds", 86400]
	 *           [false, "expiration_date", "2222-11-02 12:34:00"]
	 *           [false, "expiration_seconds", 86400]
	 *
	 * @param bool       $is_public True if the file is to be created as public.
	 * @param string     $expiration_key 'expiration_date' or 'expiration_seconds'.
	 * @param string|int $expiration_value An expiration date string or an expiration seconds number.
	 */
	public function test_create_file_request_works_if_paramters_are_good( bool $is_public, string $expiration_key, $expiration_value ) {
		$this->mock_user_capability( 'create_transient_file' );
		$parameters = array(
			'template_name' => 'simple',
			$expiration_key => $expiration_value,
			'is_public'     => $is_public,
			'variables'     => array(
				'foo' => 'TheFoo',
				'bar' => 'TheBar',
			),
			'metadata'      => array( 'meta' => 'data' ),
		);

		$response = $this->do_rest_post_request( 'files/transient/render', $parameters );
		$this->assertEquals( 201, $response->get_status() );

		$response_body = $response->get_data();
		$this->assertIsNumeric( $response_body['id'] );
		$this->assertNotEmpty( $response_body['file_name'] );
		$this->assertEquals( '2222-11-01 12:34:00', $response_body['date_created_gmt'] );
		$this->assertEquals( '2222-11-02 12:34:00', $response_body['expiration_date_gmt'] );
		$this->assertEquals( $is_public, $response_body['is_public'] );
		$this->assertFalse( $response_body['has_expired'] );
		if ( $is_public ) {
			$this->assertEquals( get_site_url() . '/wc/file/transient/' . $response_body['file_name'], $response_body['public_url'] );
		} else {
			$this->assertArrayNotHasKey( 'public_url', $response_body );
		}

		$file_data = wc_get_container()->get( TransientFilesEngine::class )->get_file_by_id( $response_body['id'], true );

		$expected_file_data = array(
			'id'                  => $response_body['id'],
			'file_name'           => $response_body['file_name'],
			'date_created_gmt'    => '2222-11-01 12:34:00',
			'expiration_date_gmt' => '2222-11-02 12:34:00',
			'is_public'           => $is_public,
			'file_path'           => self::$transient_files_dir . '/2222-11-02/' . $response_body['file_name'],
			'has_expired'         => false,
			'metadata'            => array(
				'meta' => 'data',
			),
		);

		$this->assertEquals( $expected_file_data, $file_data );
	}

	/**
	 * @testdox The get file info endpoint returns "unauthorized" error if the user doesn't have the "read_transient_file" permission.
	 */
	public function test_get_file_info_returns_unauthorized_error_if_user_doesnt_have_create_permission(): void {
		$response = $this->do_rest_get_request( 'files/transient/1/info' );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_cannot_view',
			'message' => 'Sorry, you cannot view resources.',
			'data'    => array(
				'status' => 401,
			),
		);

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The get file info endpoint returns "file not found" error if no file exists with the supplied id.
	 */
	public function test_get_file_info_returns_not_found_error_if_file_does_not_exist(): void {
		$this->mock_user_capability( 'read_transient_file' );
		$response = $this->do_rest_get_request( 'files/transient/1/info' );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_not_found',
			'message' => 'File not found',
			'data'    => array(
				'status' => 404,
			),
		);

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The get file info endpoint returns the appropriate information for an existing  file given its id.
	 *
	 * @testWith [true, true]
	 *           [true, false]
	 *           [false, true]
	 *           [false, false]
	 * @param bool $is_public True to create the file as public.
	 * @param bool $include_metadata True to request the file metadata to be returned too.
	 */
	public function test_get_file_info_returns_info_for_existing_file( bool $is_public, bool $include_metadata ): void {
		$engine    = wc_get_container()->get( TransientFilesEngine::class );
		$file_name = $engine->create_file_by_rendering_template(
			'simple',
			array(),
			array(
				'meta'               => 'data',
				'expiration_seconds' => 60,
				'is_public'          => $is_public,
			)
		);
		$file_data = $engine->get_file_by_name( $file_name );

		$this->mock_user_capability( 'read_transient_file' );
		$response = $this->do_rest_get_request( "files/transient/{$file_data['id']}/info", $include_metadata ? array( 'include_metadata' => 'true' ) : null );

		$this->assertEquals( 200, $response->get_status() );

		$response_body = $response->get_data();

		$expected_file_data = array(
			'id'                  => $response_body['id'],
			'file_name'           => $response_body['file_name'],
			'date_created_gmt'    => '2222-11-01 12:34:00',
			'expiration_date_gmt' => '2222-11-01 12:35:00',
			'is_public'           => $is_public,
			'has_expired'         => false,
		);
		if ( $include_metadata ) {
			$expected_file_data['metadata'] = array(
				'meta' => 'data',
			);
		}
		if ( $is_public ) {
			$expected_file_data['public_url'] = get_site_url() . '/wc/file/transient/' . $response_body['file_name'];
		}

		$this->assertEquals( $expected_file_data, $response_body );
	}

	/**
	 * @testdox The delete file endpoint returns "unauthorized" error if the user doesn't have the "delete_transient_file" permission.
	 */
	public function test_delete_file_returns_unauthorized_error_if_user_doesnt_have_delete_permission(): void {
		$response = $this->do_rest_request( 'files/transient/1', 'DELETE' );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_cannot_delete',
			'message' => 'Sorry, you cannot delete resources.',
			'data'    => array(
				'status' => 401,
			),
		);

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The delete file endpoint returns false if no file exists with the specified id.
	 */
	public function test_delete_file_returns_false_for_not_existing_file(): void {
		$this->mock_user_capability( 'delete_transient_file' );
		$response = $this->do_rest_request( 'files/transient/1', 'DELETE' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'deleted' => false ), $response->get_data() );
	}

	/**
	 * @testdox The delete file endpoint deletes an existing file by id and then returns false.
	 */
	public function test_delete_file_deleted_existing_file(): void {
		$engine    = wc_get_container()->get( TransientFilesEngine::class );
		$file_name = $engine->create_file_by_rendering_template( 'simple', array(), array( 'expiration_seconds' => 60 ) );
		$file_data = $engine->get_file_by_name( $file_name );
		$id        = $file_data['id'];

		$this->mock_user_capability( 'delete_transient_file' );
		$response = $this->do_rest_request( "files/transient/$id", 'DELETE' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'deleted' => true ), $response->get_data() );

		$file_data = wc_get_container()->get( TransientFilesEngine::class )->get_file_by_id( $id );
		$this->assertNull( $file_data );

		$response = $this->do_rest_request( "files/transient/$id", 'DELETE' );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'deleted' => false ), $response->get_data() );
	}

	/**
	 * @testdox The get file contents endpoint returns "unauthorized" error if the user doesn't have the "read_transient_file" permission.
	 */
	public function test_get_file_contents_unauthorized_error_if_user_doesnt_have_view_permission(): void {
		$response = $this->do_rest_request( 'files/transient/1', 'GET' );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_cannot_view',
			'message' => 'Sorry, you cannot view resources.',
			'data'    => array(
				'status' => 401,
			),
		);

		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The get file contents endpoint returns "file not found" error if no file exists with the specified id.
	 */
	public function test_get_file_contents_not_found_error_if_file_doesnt_exist(): void {
		$this->mock_user_capability( 'read_transient_file' );
		$response = $this->do_rest_request( 'files/transient/1', 'GET' );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_not_found',
			'message' => 'File not found',
			'data'    => array(
				'status' => 404,
			),
		);

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The get file contents endpoint returns the contents of the file that exists with the specified id, even if it's not public.
	 *
	 * Testing this endpoint is a bit contrived since it doesn't return JSON but the raw file contents. To achieve that
	 * it manually sets the response content type and length headers, sends the file contents as output with "echo", and finally
	 * terminates the request with "exit". Thus we need to:
	 *
	 * 1. Capture the "echo"s with "ob_start" and friends.
	 *
	 * 2. Mock the "exit" so that it throws an exception that aborts the request but not the PHP execution
	 *    (otherwise "exit" prematurely ends the test). Therefore...
	 *
	 * 3. Ignore the response returned by "do_rest_get_request", it will always be a 500 caused by the exception that we threw.
	 *
	 * 3. Capture the headers as they are set, since the exception will prevent them from being actually sent.
	 *
	 * @testWith [true, null]
	 *           [true, "fizz/buzz"]
	 *           [false, null]
	 *           [false, "fizz/buzz"]
	 *
	 * @param bool        $is_public True to create the file as public.
	 * @param string|null $content_type Content type to include as part of the file metadata, null to not include any.
	 */
	public function test_get_file_contents_returns_contents_for_existing_file( bool $is_public, ?string $content_type ): void {
		$actual_headers = array();
		$actual_status  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'header'        => function( $header ) use ( &$actual_headers ) {
					$actual_headers[] = $header;
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					$actual_status = $status;
				},
			)
		);

		$this->register_exit_mock(
			function() {
				throw new \Exception();
			}
		);

		$engine   = wc_get_container()->get( TransientFilesEngine::class );
		$metadata = array(
			'expiration_seconds' => 60,
			'is_public'          => $is_public,
		);
		if ( ! is_null( $content_type ) ) {
			$metadata['content-type'] = $content_type;
		}
		$file_name = $engine->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'theFoo',
				'bar' => 'theBar',
			),
			$metadata
		);
		$file_data = $engine->get_file_by_name( $file_name );

		$this->mock_user_capability( 'read_transient_file' );

		ob_start();
		$this->do_rest_get_request( "files/transient/{$file_data['id']}" );
		$response_body = ob_get_clean();

		$this->assertEquals( 200, $actual_status );
		$this->assertEquals( 'Simple: foo = theFoo, bar = theBar', $response_body );

		$expected_headers = array(
			'Content-Type: ' . ( $content_type ?? 'text/html' ),
			'Content-Length: 34',
		);
		$this->assertEquals( $expected_headers, $actual_headers );
	}

	/**
	 * @testdox The get file contents endpoint returns a "not found" error for a file that has expired.
	 */
	public function test_get_file_contents_returns_not_found_error_for_expired_file() {
		$this->mock_user_capability( 'read_transient_file' );

		add_filter( 'woocommerce_transient_files_minimum_expiration_seconds', fn( $seconds ) => -10000 );

		$engine = wc_get_container()->get( TransientFilesEngine::class );

		try {
			$file_name = $engine->create_file_by_rendering_template(
				'simple',
				array(),
				array(
					'expiration_seconds' => -1000,
					'is_public'          => true,
				)
			);
			$file_data = $engine->get_file_by_name( $file_name );
		} finally {
			remove_all_filters( 'woocommerce_transient_files_minimum_expiration_seconds' );
		}

		$response = $this->do_rest_get_request( "files/transient/{$file_data['id']}" );

		$expected_response_body = array(
			'code'    => 'woocommerce_rest_not_found',
			'message' => 'File not found',
			'data'    => array(
				'status' => 404,
			),
		);

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( $expected_response_body, $response->get_data() );
	}

	/**
	 * @testdox The public endpoint returns the contents of a public, non-expired existing file given its file name.
	 *
	 * Testing this endpoint is even more contrived than the authenticated one: we can't use "do_rest_get_request"
	 * because the endpoint is not registered as a JSON endpoint. Instead, we need to manually set the request global
	 * variables and then trigger the "parse_request" endpoint.
	 *
	 * Also, as in the case of the equivalent authenticated endpoint, we need to capture the headers as they are sent,
	 * capture the output with "ob_*" methods, and mock "exit" so that it throws an exception.
	 *
	 * @testWith [null]
	 *           ["fizz/buzz"]
	 *
	 * @param string|null $content_type Content type to include as part of the file metadata, null to not include any.
	 */
	public function test_public_endpoint_returns_contents_for_existing_public_file( ?string $content_type ): void {
		$actual_headers = array();
		$actual_status  = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'header'        => function( $header ) use ( &$actual_headers ) {
					$actual_headers[] = $header;
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					$actual_status = $status;
				},
			)
		);

		$this->register_exit_mock(
			function() {
				throw new \LogicException();
			}
		);

		$metadata = array(
			'expiration_seconds' => 60,
			'is_public'          => true,
		);
		if ( ! is_null( $content_type ) ) {
			$metadata['content-type'] = $content_type;
		}
		$file_name = wc_get_container()->get( TransientFilesEngine::class )->create_file_by_rendering_template(
			'simple',
			array(
				'foo' => 'theFoo',
				'bar' => 'theBar',
			),
			$metadata
		);

		ob_start();
		$_GET['wc-transient-file-name'] = $file_name;
		$_SERVER['REQUEST_METHOD']      = 'GET';
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'parse_request' );
		} catch ( \LogicException $ex ) {
			$response_body = ob_get_clean();
		}

		$this->assertEquals( 200, $actual_status );
		$this->assertEquals( 'Simple: foo = theFoo, bar = theBar', $response_body );

		$expected_headers = array(
			'Content-Type: ' . ( $content_type ?? 'text/html' ),
			'Content-Length: 34',
		);
		$this->assertEquals( $expected_headers, $actual_headers );
	}

	/**
	 * @testdox The public endpoint returns "file not found" for existing files that either aren't public or have expired.
	 *
	 * As in the successful case, we need to capture the headers, mock "exit" so that it throws an exception,
	 * and manually trigger the "parse_request" action.
	 *
	 * @testWith [true, true]
	 *           [false, false]
	 *
	 * @param bool $is_public True to create the file as public, false otherwise.
	 * @param bool $expired True to create a file that has expired, false otherwise.
	 */
	public function test_public_endpoint_returns_not_found_for_existing_not_public_or_expired_file( bool $is_public, bool $expired ): void {
		$actual_status = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'header'        => function( $header ) {
					// We aren't interested in headers, but we still need to mock the function
					// to prevent "headers already sent" errors being thrown in the PHPUnit context.
				},
				'status_header' => function( $status ) use ( &$actual_status ) {
					// The 500 is caused by the LogicException that is thrown by "exit"
					// after setting the real status code, thus we need to ignore it
					// and keep the real code that had been set previously.
					if ( 500 !== $status ) {
						$actual_status = $status;
					}
				},
			)
		);

		$this->register_exit_mock(
			function() {
				throw new \LogicException();
			}
		);

		if ( $expired ) {
			add_filter( 'woocommerce_transient_files_minimum_expiration_seconds', fn( $seconds ) => -10000 );
		}

		$metadata  = array(
			'expiration_seconds' => $expired ? -100 : 60,
			'is_public'          => $is_public,
		);
		$file_name = wc_get_container()->get( TransientFilesEngine::class )->create_file_by_rendering_template( 'simple', array(), $metadata );

		ob_start();
		$_GET['wc-transient-file-name'] = $file_name;
		$_SERVER['REQUEST_METHOD']      = 'GET';
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'parse_request' );
		} catch ( \LogicException $ex ) {
			$response_body = ob_get_clean();
		} finally {
			if ( $expired ) {
				remove_all_filters( 'woocommerce_transient_files_minimum_expiration_seconds' );
			}
		}

		$this->assertEquals( 404, $actual_status );
		$this->assertEquals( '', $response_body );
	}
}
