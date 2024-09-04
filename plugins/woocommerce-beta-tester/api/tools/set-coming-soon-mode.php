<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonCacheInvalidator;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/update-coming-soon-mode/v1',
	'tools_set_coming_soon_mode',
	array(
		'methods' => 'POST',
		'args'    => array(
			'mode' => array(
				'description' => 'Coming soon mode',
				'type'        => 'enum',
				'enum'        => array( 'site', 'store', 'disabled' ),
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-force-coming-soon-mode/v1',
	'tools_get_coming_soon_mode',
	array(
		'methods' => 'GET',
	)
);

/**
 * A tool to set the coming soon mode.
 *
 * @param WP_REST_Request $request Request object.
 */
function tools_set_coming_soon_mode( $request ) {
	$mode = $request->get_param( 'mode' );

	update_option( 'wc_admin_test_helper_force_coming_soon_mode', $mode );

	wc_get_container()->get( ComingSoonCacheInvalidator::class )->invalidate_caches();

	return new WP_REST_Response( $mode, 200 );
}

/**
 * A tool to get the coming soon mode.
 */
function tools_get_coming_soon_mode() {
	$mode = get_option( 'wc_admin_test_helper_force_coming_soon_mode', 'disabled' );

	return new WP_REST_Response( $mode, 200 );
}
