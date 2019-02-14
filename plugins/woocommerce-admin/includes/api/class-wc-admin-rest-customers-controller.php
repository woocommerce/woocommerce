<?php
/**
 * REST API Customers Controller
 *
 * Handles requests to /customers/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customers controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_Admin_REST_Reports_Customers_Controller
 */
class WC_Admin_REST_Customers_Controller extends WC_Admin_REST_Reports_Customers_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'customers';
}
