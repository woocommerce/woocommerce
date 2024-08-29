<?php

use function WP_CLI\Utils\esc_like;

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/options',
	'wca_test_helper_get_options',
	array(
		'methods' => 'GET',
		'args'    => array(
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
		'args'    => array(
			'option_names' => array(
				'type' => 'string',
			),
		),
	)
);

/**
 * A helper to delete options.
 *
 * @param WP_REST_Request $request The full request data.
 */
function wca_test_helper_delete_option( $request ) {
	global $wpdb;
	$option_names  = explode( ',', $request->get_param( 'option_names' ) );
	$option_tokens = implode( ',', array_fill( 0, count( $option_names ), '%s' ) );

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}options WHERE option_name IN ({$option_tokens})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			...$option_names,
		)
	);

	wp_cache_flush();
	return new WP_REST_RESPONSE( null, 204 );
}

/**
 * A helper to get options.
 *
 * @param WP_REST_Request $request The full request data.
 */
function wca_test_helper_get_options( $request ) {
	global $wpdb;

	$per_page = $request->get_param( 'per_page' );
	$page     = $request->get_param( 'page' );
	$search   = $request->get_param( 'search' );

	$query = "SELECT option_id, option_name, option_value, autoload FROM {$wpdb->prefix}options";

	if ( $search ) {
		$search = $wpdb->esc_like( $search );
		$search = "%{$search}%";
		$query .= ' WHERE option_name LIKE %s';
	}

	$query .= ' ORDER BY option_id DESC LIMIT %d OFFSET %d';
	$offset = ( $page - 1 ) * $per_page;

	if ( $search ) {
		$query = $wpdb->prepare( $query, $search, $per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	} else {
		$query = $wpdb->prepare( $query, $per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	$options = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return new WP_REST_Response( $options, 200 );
}

