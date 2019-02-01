<?php
/**
 * REST API Taxes Controller
 *
 * Handles requests to /taxes/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Taxes controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Taxes_Controller
 */
class WC_Admin_REST_Taxes_Controller extends WC_REST_Taxes_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

}
