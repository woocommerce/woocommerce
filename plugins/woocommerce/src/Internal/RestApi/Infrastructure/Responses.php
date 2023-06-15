<?php
/**
 * Responses class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure;

/**
 * Utility class to generate commonly used responses for REST API requests.
 */
class Responses {

	/**
	 * Generate an error response with the format of WP_Error.
	 *
	 * @param int         $http_status HTTP status code for the response.
	 * @param string|null $code Error code for the response.
	 * @param string|null $message Error message for the response.
	 * @return \WP_Error
	 */
	public static function wp_error( int $http_status, string $code = null, string $message = null ) : \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $http_status ) );
	}

	/**
	 * Generate a response with a given HTTP status and body content.
	 *
	 * @param int   $http_status HTTP status code for the response.
	 * @param mixed $content Content for the body of the response (will be returned as JSON).
	 * @return \WP_REST_Response
	 */
	public static function status_and_content( $http_status, $content = null ) : \WP_REST_Response {
		return new \WP_REST_Response( $content, $http_status );
	}

	/**
	 * Generate an "Internal server error" response.
	 *
	 * @param string|null $code Error code for the response.
	 * @param string|null $message Error message for the response.
	 * @return \WP_Error
	 */
	public static function internal_server_error( string $code = null, string $message = null ) : \WP_Error {
		return self::wp_error(
			500,
			$code ?? 'internal_server_error',
			$message ?? 'Sorry, something went wrong'
		);
	}

	/**
	 * Generate an "Unauthorized" response.
	 *
	 * @param string|null $code Error code for the response.
	 * @param string|null $message Error message for the response.
	 * @return \WP_Error
	 */
	public static function unauthorized( string $code = null, string $message = null ) : \WP_Error {
		return self::wp_error(
			401,
			$code ?? 'unauthorized',
			$message ?? 'Sorry, you are not allowed to do that'
		);
	}

	/**
	 * Generate an "Not found" response.
	 *
	 * @param string|null $code Error code for the response.
	 * @param string|null $message Error message for the response.
	 * @return \WP_Error
	 */
	public static function not_found( string $code = null, string $message = null ) : \WP_Error {
		return self::wp_error(
			401,
			$code ?? 'not_found',
			$message ?? 'Sorry, the resource you were looking for does not exist.'
		);
	}

	/**
	 * Generate an "Invalid request" response.
	 *
	 * @param string|null $code Error code for the response.
	 * @param string|null $message Error message for the response.
	 * @return \WP_Error
	 */
	public static function invalid_request( string $code = null, string $message = null ) : \WP_Error {
		return self::wp_error(
			400,
			$code ?? 'invalid_request',
			$message ?? 'Sorry, the request is invalid.'
		);
	}
}
