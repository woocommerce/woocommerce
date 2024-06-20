<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/request-redirects',
	array( WCA_Test_Helper_Remote_Get_Redirector::class, 'create_or_update' ),
	array(
		'methods' => 'POST',
		'args'    => array(
			'original_endpoint'     => array(
				'description'       => 'Target endpoint to redirect from',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'new_endpoint' => array(
				'description'       => 'Target endpoint to redirect to',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'username'  => array(
				'description'       => 'Optional HTTP Auth username',
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'password'  => array(
				'description'       => 'Optional HTTP Auth password',
				'type'              => 'string',
				'required'          => false,
			),
			'index'  => array(
				'description'       => 'Index of the redirector to update',
				'type'              => 'number',
				'required'          => false,
			),
			'enabled'  => array(
				'description'       => 'Whether the redirector is enabled or not',
				'type'              => 'boolean',
				'required'          => false,
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/request-redirects',
	array( WCA_Test_Helper_Remote_Get_Redirector::class, 'delete' ),
	array(
		'methods' => 'DELETE',
		'args'    => array(
			'index' => array(
				'description' => 'Index of the redirector to delete',
				'type'        => 'integer',
				'required'    => true,
			),
		),
	)
);


register_woocommerce_admin_test_helper_rest_route(
	'/request-redirects/(?P<index>\d+)/toggle',
	array( WCA_Test_Helper_Remote_Get_Redirector::class, 'toggle' ),
	array(
		'methods' => 'POST',
	)
);

/**
 * Class WCA_Test_Helper_Remote_Get_Redirector.
 */
class WCA_Test_Helper_Remote_Get_Redirector {
	const OPTION_NAME = 'wc-admin-test-helper-request-redirects';

	/**
	 * Create a redirector.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function create_or_update( $request ) {
		$params = [
			'original_endpoint' => $request->get_param('original_endpoint'),
			'new_endpoint' => $request->get_param('new_endpoint'),
			'username' => $request->get_param('username'),
			'password' => $request->get_param('password'),
			'enabled' => $request->get_param('enabled'),
			'index' => $request->get_param('index')
		];

		$redirector = [
			'original_endpoint' => $params['original_endpoint'],
			'new_endpoint' => $params['new_endpoint'],
			'username' => $params['username'],
			'password' => $params['password'],
			'enabled' => $params['enabled']
		];

		self::update(function($redirectors) use ($params, $redirector) {
			if ($params['index'] !== null) {
				$redirectors[$params['index']] = $redirector;
			} else {
				$redirector['enabled'] = true;
				$redirectors[] = $redirector;
			}
			return $redirectors;
		});

    	return new WP_REST_RESPONSE( [], 200);
	}

	/**
	 * Update the filters.
	 *
	 * @param callable $callback Callback to update the filters.
	 * @return bool
	 */
	public static function update( callable $callback ) {
		$filters = get_option( self::OPTION_NAME, array() );
		$filters = $callback( $filters );
		return update_option( self::OPTION_NAME, $filters );
	}

	/**
	 * Delete a filter.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function delete( $request ) {
		self::update(
			function ( $filters ) use ( $request ) {
				array_splice( $filters, $request->get_param( 'index' ), 1 );
				return $filters;
			}
		);

		return new WP_REST_RESPONSE( [], 200 );
	}

	/**
	 * Toggle a filter on or off.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function toggle( $request ) {
		self::update(
			function ( $filters ) use ( $request ) {
				$index                        = $request->get_param( 'index' );
				$filters[ $index ]['enabled'] = ! $filters[ $index ]['enabled'];
				return $filters;
			}
		);
		return new WP_REST_RESPONSE( [], 200 );
	}
}
