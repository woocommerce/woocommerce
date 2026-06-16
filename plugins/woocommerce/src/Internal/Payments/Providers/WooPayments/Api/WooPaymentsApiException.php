<?php
/**
 * WooPaymentsApiException class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use RuntimeException;

/**
 * Exception thrown by the native WooPayments provider transport.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsApiException extends RuntimeException {

	/**
	 * Provider error code.
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	private int $http_code;

	/**
	 * Constructor.
	 *
	 * @param string $message    Exception message.
	 * @param string $error_code Provider error code.
	 * @param int    $http_code  HTTP status code.
	 */
	public function __construct( string $message, string $error_code = '', int $http_code = 0 ) {
		parent::__construct( $message );

		$this->error_code = $error_code;
		$this->http_code  = $http_code;
	}

	/**
	 * Get the provider error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return int
	 */
	public function get_http_code(): int {
		return $this->http_code;
	}
}
