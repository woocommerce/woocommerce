<?php

register_woocommerce_admin_test_helper_rest_route(
	'/options',
	'wca_test_helper_get_options',
	array(
		'methods' => 'GET',
		'args'                => array(
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
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/options/(?P<option_id>\d+)',
	'wca_test_helper_delete_option',
	array(
		'methods' => 'DELETE',
		'args'                => array(
			'option_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		),
	)
);

function wca_test_helper_delete_option( $request ) {
	global $wpdb;
	$option_id = $request->get_param( 'option_id' );
	$query     = $wpdb->prepare( "delete from {$wpdb->prefix}options where option_id = %d", $option_id );
	$wpdb->query( $query );

	return new WP_REST_RESPONSE( null, 204 );
}

function wca_test_helper_get_options( $request ) {
	global $wpdb;

	$per_page = $request->get_param( 'per_page' );
	$page     = $request->get_param( 'page' );
	$search   = $request->get_param( 'search' );

	$query = "
        select option_id, option_name, option_value, autoload
        from {$wpdb->prefix}options
    ";

	if ( $search ) {
		$query .= "where option_name like '%{$search}%'";
	}

	$query .= ' order by option_id desc limit 30';

	$options = $wpdb->get_results( $query );

	return new WP_REST_Response( $options, 200 );
}

