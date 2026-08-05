<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\ErrorType;
use WP_REST_Response;

/**
 * Error class.
 *
 * Represents an error object as defined in the Agentic Commerce Protocol.
 * This class handles API-level errors with type, code, message, and optional param.
 */
class Error {
	/**
	 * The error type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Implementation-defined error code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Human-readable error message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * RFC 9535 JSONPath to the problematic parameter (optional).
	 *
	 * @var string|null
	 */
	private $param;

	/**
	 * Constructor.
	 *
	 * @param string      $type    Error type from ErrorType enum.
	 * @param string      $code    Implementation-defined error code.
	 * @param string      $message Human-readable error message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 */
	private function __construct( $type, $code, $message, $param = null ) {
		$this->type    = $type;
		$this->code    = $code;
		$this->message = $message;
		$this->param   = $param;
	}

	/**
	 * Create an invalid request error.
	 *
	 * @param string      $code    Implementation-defined error code.
	 * @param string      $message Human-readable error message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return Error
	 */
	public static function invalid_request( $code, $message, $param = null ) {
		return new self( ErrorType::INVALID_REQUEST, $code, $message, $param );
	}

	/**
	 * Create a request not idempotent error.
	 *
	 * @param string      $code    Implementation-defined error code.
	 * @param string      $message Human-readable error message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return Error
	 */
	public static function request_not_idempotent( $code, $message, $param = null ) {
		return new self( ErrorType::REQUEST_NOT_IDEMPOTENT, $code, $message, $param );
	}

	/**
	 * Create a processing error.
	 *
	 * @param string      $code    Implementation-defined error code.
	 * @param string      $message Human-readable error message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return Error
	 */
	public static function processing_error( $code, $message, $param = null ) {
		return new self( ErrorType::PROCESSING_ERROR, $code, $message, $param );
	}

	/**
	 * Create a service unavailable error.
	 *
	 * @param string      $code    Implementation-defined error code.
	 * @param string      $message Human-readable error message.
	 * @param string|null $param   RFC 9535 JSONPath (optional).
	 * @return Error
	 */
	public static function service_unavailable( $code, $message, $param = null ) {
		return new self( ErrorType::SERVICE_UNAVAILABLE, $code, $message, $param );
	}

	/**
	 * Convert the error to a WP_REST_Response.
	 *
	 * @return WP_REST_Response WordPress REST API response object
	 */
	public function to_rest_response() {
		$data = array(
			'type'    => $this->type,
			'code'    => $this->code,
			'message' => $this->message,
		);

		if ( null !== $this->param ) {
			$data['param'] = $this->param;
		}

		$status_code = $this->get_http_status_code();

		return new WP_REST_Response( $data, $status_code );
	}

	/**
	 * Determine HTTP status code based on error type.
	 *
	 * @return int HTTP status code
	 */
	private function get_http_status_code() {
		switch ( $this->type ) {
			case ErrorType::INVALID_REQUEST:
				return 400;
			case ErrorType::REQUEST_NOT_IDEMPOTENT:
				return 409;
			case ErrorType::PROCESSING_ERROR:
				return 500;
			case ErrorType::SERVICE_UNAVAILABLE:
				return 503;
			default:
				return 500;
		}
	}
}
