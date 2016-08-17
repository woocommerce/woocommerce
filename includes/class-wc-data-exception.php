<?php
/**
 * WooCommerce Data Exception Class
 *
 * Extends Exception to provide additional data.
 *
 * @author      WooThemes
 * @category    Core
 * @package     WooCommerce
 * @since       2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Data_Exception class
 */
class WC_Data_Exception extends Exception {

	/** @var string sanitized error code */
	protected $error_code;

	/**
	 * Setup exception.
	 *
	 * error code - machine-readable, e.g. `woocommerce_invalid_product_id`
	 * error message - friendly message, e.g. 'Product ID is invalid'
	 * http status code - proper HTTP status code to respond with, e.g. 400
	 *
	 * @param string $error_code
	 * @param string $error_message user-friendly translated error message
	 * @param int $http_status_code HTTP status code to respond with
	 */
	public function __construct( $error_code, $error_message, $http_status_code = 400 ) {
		$this->error_code = $error_code;
		parent::__construct( $error_message, $http_status_code );
	}

	/**
	 * Returns the error code
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
