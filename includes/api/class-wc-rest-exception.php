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

/**
 * WC_REST_Exception class.
 */
class WC_REST_Exception extends WC_Data_Exception {}
