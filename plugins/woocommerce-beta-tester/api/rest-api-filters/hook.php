<?php

defined( 'ABSPATH' ) || exit;

$filters = get_option( WCA_Test_Helper_Rest_Api_Filters::WC_ADMIN_TEST_HELPER_REST_API_FILTER_OPTION, array() );

/**
 * Sets an array value using dot notation.
 *
 * @param array  $array The array to set the value on.
 * @param string $key   The dot notation key to set.
 * @param mixed  $value The value to set.
 */
function array_dot_set( &$array, $key, $value ) {
	if ( is_null( $key ) ) {
		$array = $value;
		return $array;
	}

	$keys = explode( '.', $key );

	$key_count = count( $keys );
	while ( $key_count > 1 ) {
		$key = array_shift( $keys );
		if ( ! isset( $array[ $key ] ) || ! is_array( $array[ $key ] ) ) {
			$array[ $key ] = array();
		}
		$array = &$array[ $key ];

		--$key_count;
	}

	$array[ array_shift( $keys ) ] = $value;
	return $array;
}

add_filter(
	'rest_request_after_callbacks',
	function ( $response, array $handler, \WP_REST_Request $request ) use ( $filters ) {
		if ( ! $response instanceof \WP_REST_Response ) {
			return $response;
		}
		$route   = $request->get_route();
		$filters = array_filter(
			$filters,
			function ( $filter ) use ( $request, $route ) {
				if ( $filter['enabled'] && $filter['endpoint'] === $route ) {
					return true;
				}
				return false;
			}
		);

		$data = $response->get_data();

		foreach ( $filters as $filter ) {
			array_dot_set( $data, $filter['dot_notation'], $filter['replacement'] );
		}

		$response->set_data( $data );

		return $response;
	},
	10,
	3
);
