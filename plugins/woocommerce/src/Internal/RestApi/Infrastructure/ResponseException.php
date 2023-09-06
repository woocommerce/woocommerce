<?php
/**
 * ResponseException class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure;

/**
 * Represents an exception thrown during the processing of a REST API request.
 */
class ResponseException extends \Exception {

	/**
	 * The REST API response that will be sent.
	 *
	 * @var \WP_Error|\WP_REST_Response|array
	 */
	public $rest_response;

	/**
	 * Creates an instance of the class.
	 *
	 * @param \WP_Error|\WP_REST_Response|array $rest_response The REST API response that will be sent.
	 * @param string                            $message Exception message.
	 * @param int                               $code Exception code.
	 * @param Throwable|null                    $previous Previous exception.
	 */
	public function __construct( $rest_response, string $message = '', int $code = 0, ?Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->rest_response = $rest_response;
	}

	/**
	 * Creates a new instance of the class from a response object.
	 *
	 * @param \WP_Error|\WP_REST_Response|array $response The REST API response that will be sent.
	 * @return ResponseException
	 */
	public static function for_response( $response ) : ResponseException {
		return new ResponseException( $response );
	}

	/**
	 * Creates a new instance of the class from a HTTP status code and from content to be sent.
	 * Use this if you need to return a response with a given data structure and an HTTP status code other than 200
	 * (or if you want to return a HTTP status code with no content at all).
	 *
	 * @param int   $http_status_code HTTP status code to return.
	 * @param mixed $content Content for the body of the response (will be returned as JSON).
	 * @return ResponseException
	 */
	public static function for_http_status( $http_status_code, $content = null ) : ResponseException {
		return new ResponseException( Responses::status_and_content( $http_status_code, $content ) );
	}

	/**
	 * Creates a new instance of the class from a HTTP status code, an error code
	 * and an error message; the response will have the format produced by the WP_Error class.
	 *
	 * @param int    $http_status_code HTTP status code to return.
	 * @param string $error_code Error code to be included in the response.
	 * @param string $error_message Error message to be included in the response.
	 * @return ResponseException
	 */
	public static function for_error( $http_status_code, $error_code, $error_message ) : ResponseException {
		return new ResponseException( Responses::wp_error( $http_status_code, $error_code, $error_message ) );
	}

	/**
	 * Creates a new instance of the class for an "Unauthorized" HTTP error.
	 *
	 * @param string|null $code Error code to be included in the response.
	 * @param string|null $message Error message to be included in the response.
	 * @return ResponseException
	 */
	public static function for_unauthoritzed( string $code = null, string $message = null ) : ResponseException {
		return new ResponseException( Responses::unauthorized( $code, $message ) );
	}

	/**
	 * Creates a new instance of the class for an "Internal server error" HTTP error.
	 *
	 * @param string|null $code Error code to be included in the response.
	 * @param string|null $message Error message to be included in the response.
	 * @return ResponseException
	 */
	public static function for_internal_server_error( string $code = null, string $message = null ) : ResponseException {
		return new ResponseException( Responses::internal_server_error( $code, $message ) );
	}

	/**
	 * Creates a new instance of the class for a "Not found" HTTP error.
	 *
	 * @param string|null $code Error code to be included in the response.
	 * @param string|null $message Error message to be included in the response.
	 * @return ResponseException
	 */
	public static function for_not_found( string $code = null, string $message = null ) : ResponseException {
		return new ResponseException( Responses::not_found( $code, $message ) );
	}
}
