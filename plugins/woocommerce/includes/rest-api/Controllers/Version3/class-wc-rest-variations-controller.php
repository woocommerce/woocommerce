<?php
/**
 * REST API variations controller
 *
 * Handles requests to the /variations endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * REST API variations controller class.
 *
 * @package WooCommerce\RestApi
 * @extends \Automattic\WooCommerce\Admin\API\ProductVariations
 */
class WC_REST_Variations_Controller extends \Automattic\WooCommerce\Admin\API\ProductVariations {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'variations';
}
