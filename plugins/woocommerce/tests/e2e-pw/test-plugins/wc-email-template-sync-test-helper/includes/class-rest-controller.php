<?php
/**
 * Helper REST endpoints exposed by the WC Email Template Sync test helper plugin.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Helper REST endpoints exposed under /wc-email-test-helper/v1/ for Playwright E2E tests.
 *
 * Health endpoint is open; every other route requires manage_options + an X-Playwright header.
 */
class REST_Controller {

	private const NAMESPACE = 'wc-email-test-helper/v1';

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'health' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Health probe. Tests call this in beforeAll to confirm the helper plugin is loaded.
	 *
	 * @param WP_REST_Request $request The REST request (unused; REST callback signature requires it).
	 * @return WP_REST_Response
	 */
	public function health( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'version' => '1.0.0',
			),
			200
		);
	}

	/**
	 * Permission callback used by every non-health endpoint. Requires the manage_options
	 * capability plus an X-Playwright header to keep accidental hits from outside the
	 * E2E suite from making any state changes.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool
	 */
	public static function require_admin_and_playwright( WP_REST_Request $request ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return '1' === $request->get_header( 'x_playwright' );
	}
}
