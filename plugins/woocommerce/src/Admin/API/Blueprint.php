<?php



namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\QuickConfig\QuickConfigService;

class Blueprint {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'blueprint';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'process';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'process' ),
					'permission_callback' => function () {
						return true;
					},
				),
			)
		);
	}

	public function process() {
		$blueprint_schema = constant( 'WOOCOMMERCE_BLUEPRINT_PATH' );
		if ( ! $blueprint_schema ) {
			return new WP_HTTP_Response( null, 404 );
		}
		$blueprint = new \Automattic\WooCommerce\Admin\Features\Blueprint\Blueprint( $blueprint_schema );
		$blueprint->process();
		return new WP_HTTP_Response( null, 200 );
	}
}
