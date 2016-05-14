<?php
/**
 * WooCommerce REST Exception Class
 *
 * Extends Exception to provide additional data.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_REST_Exception extends Exception {

	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Setup exception.
	 *
	 * @param string $error_code Machine-readable error code.
	 * @param string $error_message User-friendly translated error message.
	 * @param int $http_status_code HTTP status code to respond with.
	 */
	public function __construct( $error_code, $error_message, $http_status_code ) {
		$this->error_code = $error_code;

		parent::__construct( $error_message, $http_status_code );
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
