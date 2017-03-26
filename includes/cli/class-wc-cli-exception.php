<?php
/**
 * WooCommerce CLI Exception Class.
 *
 * Extends Exception to provide additional data.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_CLI_Exception extends Exception {

	/** @var string sanitized error code */
	protected $error_code;

	/**
	 * Setup exception, requires 3 params:
	 *
	 * error code - machine-readable, e.g. `woocommerce_invalid_product_id`
	 * error message - friendly message, e.g. 'Product ID is invalid'
	 *
	 * @since 2.5.0
	 * @param string $error_code
	 * @param string $error_message user-friendly translated error message
	 */
	public function __construct( $error_code, $error_message ) {
		$this->error_code = $error_code;
		parent::__construct( $error_message );
	}

	/**
	 * Returns the error code
	 *
	 * @since  2.5.0
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
