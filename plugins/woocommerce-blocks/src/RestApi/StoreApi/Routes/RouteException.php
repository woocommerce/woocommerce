<?php
/**
 * Exceptions for rest routes.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Routes;

/**
 * ReserveStockRouteExceptionException class.
 */
class RouteException extends \Exception {
	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	public $error_code;

	/**
	 * Setup exception.
	 *
	 * @param string $error_code       Machine-readable error code, e.g `woocommerce_invalid_product_id`.
	 * @param string $message          User-friendly translated error message, e.g. 'Product ID is invalid'.
	 * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
	 */
	public function __construct( $error_code, $message, $http_status_code = 400 ) {
		$this->error_code = $error_code;

		parent::__construct( $message, $http_status_code );
	}

	/**
	 * Returns the error code.
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
