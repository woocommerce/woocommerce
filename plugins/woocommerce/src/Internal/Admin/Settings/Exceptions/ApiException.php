<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\Exceptions;

/**
 * ApiException class.
 */
class ApiException extends \Exception {
	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	public string $error_code;

	/**
	 * Additional error data.
	 *
	 * @var array
	 */
	public array $additional_data = array();

	/**
	 * Setup exception.
	 *
	 * @param string $error_code       Machine-readable error code, e.g `woocommerce_invalid_step_id`.
	 * @param string $message          User-friendly translated error message, e.g. 'Step ID is invalid'.
	 * @param int    $http_status_code Optional. Proper HTTP status code to respond with.
	 *                                 Defaults to 400 (Bad request).
	 * @param array  $additional_data  Optional. Extra data (key value pairs) to expose in the error response.
	 *                                 Defaults to empty array.
	 */
	public function __construct( string $error_code, string $message, int $http_status_code = 400, array $additional_data = array() ) {
		$this->error_code      = $error_code;
		$this->additional_data = array_filter( (array) $additional_data );
		parent::__construct( $message, $http_status_code );
	}

	/**
	 * Returns the error code.
	 *
	 * @return string The machine-readable error code.
	 */
	public function getErrorCode(): string {
		return $this->error_code;
	}

	/**
	 * Returns additional error data.
	 *
	 * @return array Extra data (key value pairs).
	 */
	public function getAdditionalData(): array {
		return $this->additional_data;
	}
}
