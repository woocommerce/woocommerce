<?php

namespace Automattic\WooCommerce\RestApi\V4\Controllers;

use WP_REST_Controller;
use WP_REST_Request;
use WP_Error;

/**
 * Abstract base controller for WooCommerce REST API v4.
 *
 * Extends WordPress core WP_REST_Controller directly (not legacy WC controllers).
 * Contains only what we actually need right now.
 */
abstract class AbstractController extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
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
	 * Get the item schema for this controller.
	 *
	 * Must be implemented by subclasses.
	 *
	 * @return array
	 */
	abstract public function get_item_schema();

	/**
	 * Get collection parameters for this controller.
	 *
	 * Can be overridden by subclasses.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		// Add common WooCommerce collection parameters
		$params['include'] = array(
			'description' => __( 'Limit result set to specific IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$params['exclude'] = array(
			'description' => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		return $params;
	}




	/**
	 * Handle internal server errors with appropriate logging.
	 *
	 * @param \Exception $exception The exception that occurred.
	 * @param string     $context Additional context for logging.
	 * @return WP_Error
	 */
	protected function handle_internal_error( \Exception $exception, string $context = '' ): WP_Error {
		$error_message = $context ? "{$context}: {$exception->getMessage()}" : $exception->getMessage();

		wc_get_logger()->error(
			$error_message,
			array(
				'source'    => 'woocommerce-rest-api-v4',
				'exception' => $exception,
			)
		);

		// Use WordPress standard error code and let WordPress handle the status
		return new WP_Error(
			'rest_no_route',
			__( 'No route was found matching the URL and request method.', 'woocommerce' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Get base schema structure.
	 *
	 * @return array
	 */
	protected function get_base_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-07/schema#',
			'title'      => $this->rest_base,
			'type'       => 'object',
			'properties' => array(),
		);
	}
}

