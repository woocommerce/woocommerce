<?php
/**
 * Abstract REST Controller.
 *
 * Extends WP_REST_Controller. Implements functionality that applies to all route controllers.
 *
 * @package WooCommerce\RestApi
 */

namespace Automattic\WooCommerce\RestApi\V4;

defined( 'ABSPATH' ) || exit;

/**
 * Orders Controller.
 */
abstract class AbstractController extends \WP_REST_Controller {
	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Hook prefix.
	 *
	 * @var string
	 */
	protected $hook_prefix = 'woocommerce_rest_api_v4_';

	/**
	 * Error prefix.
	 *
	 * @var string
	 */
	protected $error_prefix = 'woocommerce_rest_api_';

	/**
	 * Get the hook prefix for actions and filters. e.g. woocommerce_rest_api_v4_orders_
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->hook_prefix . $this->rest_base . '_';
	}

	/**
	 * Get the error prefix for errors. e.g. woocommerce_rest_api_v4_orders_
	 *
	 * @return string
	 */
	protected function get_error_prefix() {
		return $this->error_prefix . $this->rest_base . '_';
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema Schema.
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}
}
