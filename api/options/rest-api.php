<?php

class Options_Rest_Api extends WP_REST_Controller {
	protected $namespace = 'wc-admin-test-helper/v1';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/options',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_items' ),
				'args'                => array(
					'per_page' => $this->get_collection_params()['per_page'],
				),
				'permission_callback' => function( $request ) {
					return true;
				},
			)
		);

		register_rest_route(
			$this->namespace,
			'/options/(?P<option_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_item' ),
				'args'                => array(
					'option_id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
				'permission_callback' => function( $request ) {
					return true;
				},
			)
		);
	}

	public function delete_item( $request ) {
		global $wpdb;
		$option_id = $request->get_param( 'option_id' );
		$query     = $wpdb->prepare( "delete from {$wpdb->prefix}options where option_id = %d", $option_id );
		$wpdb->query( $query );

		return new WP_REST_RESPONSE( null, 204 );
	}

	public function get_items( $request ) {
		global $wpdb;

		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$search   = $request->get_param( 'search' );

		$query = "
            select option_id, option_name, autoload
            from {$wpdb->prefix}options
        ";

		if ( $search ) {
			$query .= "where option_name like '%{$search}%'";
		}

		$query .= ' order by option_id desc limit 30';

		$options = $wpdb->get_results( $query );

		return new WP_REST_Response( $options, 200 );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}

add_action(
	'rest_api_init',
	function() {
		( new Options_Rest_Api() )->register_routes();
	}
);
