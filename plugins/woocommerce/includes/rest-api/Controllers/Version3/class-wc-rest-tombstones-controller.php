<?php

class WC_REST_Tombstones_Controller extends WC_REST_CRUD_Controller {

	protected $namespace = 'wc/v3';

	protected $rest_base = 'tombstones';

	protected $request = array();

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	function get_items() {
		return WC_Tombstones::ids();
	}

	function get_items_permissions_check() {
		return current_user_can( 'manage_options' );
	}
}
