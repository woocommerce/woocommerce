<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\AI;

/**
 * AI Endpoint base controller
 *
 * @internal
 */
abstract class AIEndpoint {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ai';


	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint;

	/**
	 * Register routes.
	 *
	 * @param array $args Optional. Either an array of options for the endpoint,
	 * or an array of arrays for multiple methods. Default empty array.
	 */
	public function register( $args ) {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/' . $this->endpoint,
			$args
		);
	}

	/**
	 * Return schema properties.
	 *
	 * @return array
	 */
	public function get_schema() {
		return array();
	}
}
