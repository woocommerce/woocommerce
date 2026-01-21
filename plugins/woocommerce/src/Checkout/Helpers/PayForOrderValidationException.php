<?php
/**
 * Exceptions for Pay for Order validation.
 */

namespace Automattic\WooCommerce\Checkout\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * PayForOrderValidationException class.
 *
 * This exception is thrown when Pay for Order page validation fails.
 * The error code can be used for tracking and analytics purposes.
 *
 * @since 10.6.0
 */
class PayForOrderValidationException extends \Exception {
	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Setup exception.
	 *
	 * @param string $message    User-facing translated error message.
	 * @param string $error_code Machine-readable error code, e.g. 'invalid_order_key'.
	 */
	public function __construct( string $message, string $error_code ) {
		$this->error_code = $error_code;
		parent::__construct( $message );
	}

	/**
	 * Returns the error code.
	 *
	 * @return string
	 */
	public function getErrorCode(): string {
		return $this->error_code;
	}
}
