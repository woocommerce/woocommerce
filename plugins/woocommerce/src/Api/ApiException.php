<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api;

/**
 * Exception for API errors with error codes and extensions.
 */
class ApiException extends \RuntimeException {
	/**
	 * Constructor.
	 *
	 * @param string      $message    The error message.
	 * @param string      $error_code The machine-readable error code.
	 * @param array       $extensions Additional error metadata.
	 * @param int         $status_code The HTTP status code.
	 * @param ?\Throwable $previous   The previous throwable for chaining.
	 */
	public function __construct(
		string $message,
		private readonly string $error_code = 'INTERNAL_ERROR',
		private readonly array $extensions = array(),
		int $status_code = 500,
		?\Throwable $previous = null,
	) {
		parent::__construct( $message, $status_code, $previous );
	}

	/**
	 * Get the machine-readable error code.
	 *
	 * @return string
	 */
	public function getErrorCode(): string {
		return $this->error_code;
	}

	/**
	 * Get the additional error metadata.
	 *
	 * @return array
	 */
	public function getExtensions(): array {
		return $this->extensions;
	}

	/**
	 * Get the HTTP status code.
	 *
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->getCode();
	}
}
