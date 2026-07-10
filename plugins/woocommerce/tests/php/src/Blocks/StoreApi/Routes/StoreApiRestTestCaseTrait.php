<?php
/**
 * Shared REST server setup for Store API route tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Provides namespace-scoped Store API route registration.
 */
trait StoreApiRestTestCaseTrait {
	/**
	 * Create a REST server with only the relevant WooCommerce namespace loaded.
	 */
	protected function initialize_store_api_server(): void {
		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();

		$had_rest_route = array_key_exists( 'rest_route', $GLOBALS['wp']->query_vars );
		$rest_route     = $GLOBALS['wp']->query_vars['rest_route'] ?? null;
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1';

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'rest_api_init', $wp_rest_server );
		} finally {
			if ( $had_rest_route ) {
				$GLOBALS['wp']->query_vars['rest_route'] = $rest_route;
			} else {
				unset( $GLOBALS['wp']->query_vars['rest_route'] );
			}
		}
	}
}
