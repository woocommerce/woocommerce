<?php
/**
 * REST API Coupons Controller
 *
 * Handles requests to /coupons/*
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Coupons controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Coupons_Controller
 */
class WC_Admin_REST_Coupons_Controller extends WC_REST_Coupons_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

}
