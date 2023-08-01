<?php
// phpcs:disable

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
	'/options/(?P<option_names>(.*)+)',
	'wca_test_helper_delete_option',
	array(
		'methods' => 'DELETE',
		'args'                => array(
			'option_names' => array(
				'type'              => 'string',
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/transients',
	'wca_test_helper_get_transient',
	array(
		'methods' => \WP_REST_Server::READABLE,
		'args'                => array(
			'search'   => array(
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/transients',
	'wca_test_helper_add_transient',
	array(
		'methods' => \WP_REST_Server::EDITABLE,
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/transients/(?P<transient_names>(.*)+)',
	'wca_test_helper_delete_transient',
	array(
		'methods' => \WP_REST_Server::DELETABLE,
		'args'                => array(
			'transient_names' => array(
				'type' => 'string',
			),
		),
	)
);

function wca_test_helper_delete_option( $request ) {
	global $wpdb;
	$option_names = explode( ',', $request->get_param( 'option_names' ) );
    $option_names = array_map( function( $option_name ) {
        return "'" . $option_name . "'";
    }, $option_names );

    $option_names = implode( ',', $option_names );
    $query = "delete from {$wpdb->prefix}options where option_name in ({$option_names})";
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

function wca_test_helper_get_transient( $request ) {
	global $wpdb;

	$search = wc_clean( $request->get_param( 'search' ) );
	if ( empty( $search ) ) {
		return new WP_REST_RESPONSE( null, 400 );
	}

	$query = "
        select option_id, option_name, option_value, autoload
        from {$wpdb->prefix}options 
		where 
			option_name like %s
			and option_name not like '_transient_timeout%'
		order by option_id desc limit 30
    ";

	$transients = $wpdb->get_results( $wpdb->prepare( $query, '_transient_' . $search . '%',  ) );

	return new WP_REST_Response( $transients, 200 );
}

function wca_test_helper_add_transient( $request ) {
	$params  = $request->get_json_params();
	$updated = array();

	if ( ! is_array( $params ) ) {
		return new WP_REST_RESPONSE( null, 400 );
	}

	// Construct the transient data.
	// Hint:
	// $value is an array containing the value and the timeout. 
	$transients = array();
	foreach ( $params as $key => $value ) {
		set_transient( wc_clean( $key ), wc_clean( $value[0] ), absint( $value[1] ) );
	}

	return new WP_REST_RESPONSE( null, 204 );
}

function wca_test_helper_delete_transient( $request ) {
	$transient_names = explode( ',', $request->get_param( 'transient_names' ) );
	$transient_names = array_map( 'wc_clean', $transient_names );

	foreach ( $transient_names as $transient ) {
		delete_transient( $transient );
	}

	return new WP_REST_RESPONSE( null, 204 );
}
