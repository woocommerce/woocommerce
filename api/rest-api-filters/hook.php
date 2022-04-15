<?php

$filters = get_option(WCA_Test_Helper_Rest_Api_Filters::WC_ADMIN_TEST_HELPER_REST_API_FILTER_OPTION, [] );

function array_dot_set( &$array, $key, $value ) {
    if ( is_null( $key ) ) {
        return $array = $value;
    }

    $keys = explode('.', $key);

    while ( count($keys) > 1 ) {
        $key = array_shift($keys);
        if (! isset($array[$key]) || ! is_array($array[$key]) ) {
            $array[$key] = [];
        }
        $array = &$array[$key];
    }

    $array[ array_shift($keys) ] = $value;
    return $array;
}

add_filter(
    'rest_request_after_callbacks',
    function ( $response, array $handler, \WP_REST_Request $request ) use ( $filters ) {
        if (! $response instanceof  \WP_REST_Response ) {
            return $response;
        }
        $route = $request->get_route();
        $filters = array_filter(
            $filters, function ( $filter ) use ( $request, $route ) {
                if ($filter['enabled'] && $filter['endpoint'] == $route ) {
                    return true;
                }
                return false;
            }
        );

        $data = $response->get_data();

        foreach ( $filters as $filter ) {
            array_dot_set($data, $filter['dot_notation'], $filter['replacement']);
        }

        $response->set_data($data);

        return $response;
    },
    10,
    3
);