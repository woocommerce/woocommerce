<?php
/**
 * API initialization for beta testing.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the test helper route.
 *
 * @param string $route Route path.
 * @param string $callback Callback function name.
 * @param array  $additional_options Additional options passed to route registration.
 */
function register_woocommerce_admin_test_helper_rest_route( $route, $callback, $additional_options = array() ) {
	add_action(
		'rest_api_init',
		function() use ( $route, $callback, $additional_options ) {
			$default_options = array(
				'methods'             => 'POST',
				'callback'            => $callback,
				'permission_callback' => function( $request ) {
					if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
						return new \WP_Error(
							'woocommerce_rest_cannot_edit',
							__( 'Sorry, you cannot perform this action', 'woocommerce-beta-tester' )
						);
					}
					return true;
				},
			);

			$default_options = array_merge( $default_options, $additional_options );

			register_rest_route(
				'wc-admin-test-helper',
				$route,
				$default_options
			);
		}
	);
}

require 'admin-notes/delete-all-notes.php';
require 'admin-notes/add-note.php';
require 'tools/trigger-wca-install.php';
require 'tools/trigger-cron-job.php';
require 'tools/run-wc-admin-daily.php';
require 'options/rest-api.php';
require 'tools/delete-all-products.php';
require 'tools/disable-wc-email.php';
require 'tools/trigger-update-callbacks.php';
require 'tools/reset-cys.php';
require 'tracks/class-tracks-debug-log.php';
require 'features/features.php';
require 'rest-api-filters/class-wca-test-helper-rest-api-filters.php';
require 'rest-api-filters/hook.php';
require 'live-branches/manifest.php';
require 'live-branches/install.php';
require 'tools/set-block-template-logging-threshold.php';
require 'remote-spec-validator/class-wca-test-helper-remote-spec-validator.php';
