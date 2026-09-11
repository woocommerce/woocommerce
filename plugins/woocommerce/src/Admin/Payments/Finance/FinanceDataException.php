<?php
/**
 * Finance data exception.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance;

defined( 'ABSPATH' ) || exit;

/**
 * Exception finance data providers throw to surface a user-presentable error over the REST API.
 *
 * The error code is prefixed with `woocommerce_rest_payments_finance_` in the REST response and the
 * HTTP status is used as the response status. Any other exception a provider throws is logged and
 * reported as a generic 500.
 *
 * @since 11.2.0
 */
class FinanceDataException extends \Exception {

	/**
	 * Machine-readable error code.
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * HTTP status for the REST response.
	 *
	 * @var int
	 */
	private int $http_status;

	/**
	 * Constructor.
	 *
	 * @param string          $message     User-presentable error message.
	 * @param string          $error_code  Machine-readable error code, lowercase letters, digits and underscores.
	 * @param int             $http_status HTTP status for the REST response, 400-599.
	 * @param \Throwable|null $previous    The previous exception, if any.
	 *
	 * @since 11.2.0
	 */
	public function __construct( string $message, string $error_code = 'provider_error', int $http_status = 500, ?\Throwable $previous = null ) {
		parent::__construct( $message, 0, $previous );

		$this->error_code  = $error_code;
		$this->http_status = $http_status;
	}

	/**
	 * Get the machine-readable error code.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get the HTTP status for the REST response.
	 *
	 * @return int
	 *
	 * @since 11.2.0
	 */
	public function get_http_status(): int {
		return $this->http_status;
	}
}
