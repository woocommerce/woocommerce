<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;


class Init {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function init_rest_api() {
		$controller = new RestApi();
		$controller->register_routes();
	}
}
