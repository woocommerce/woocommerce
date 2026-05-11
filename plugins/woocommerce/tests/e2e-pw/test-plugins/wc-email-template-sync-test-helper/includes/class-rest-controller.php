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

		register_rest_route(
			self::NAMESPACE,
			'/reset-post/(?P<email_id>[a-z0-9_]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'reset_post' ),
				'permission_callback' => array( self::class, 'require_admin_and_playwright' ),
				'args'                => array(
					'email_id' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/seed-meta/(?P<post_id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'seed_meta' ),
				'permission_callback' => array( self::class, 'require_admin_and_playwright' ),
				'args'                => array(
					'post_id' => array( 'sanitize_callback' => 'absint' ),
				),
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
	 * Delete the woo_email post for the given email type, clear template manager state +
	 * transient, then regenerate synchronously.
	 *
	 * @param WP_REST_Request $request The REST request. Expects `email_id` route parameter.
	 * @return WP_REST_Response
	 */
	public function reset_post( WP_REST_Request $request ): WP_REST_Response {
		$email_id = (string) $request->get_param( 'email_id' );

		$manager = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager::get_instance();

		$existing_post_id = (int) $manager->get_email_template_post_id( $email_id );
		if ( $existing_post_id > 0 ) {
			wp_delete_post( $existing_post_id, true );
		}

		$manager->delete_email_template( $email_id );

		delete_transient( 'wc_email_editor_initial_templates_generated' );

		$generator = new \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator();
		$generator->init_default_transactional_emails();
		$new_post_id = (int) $generator->generate_email_template_if_not_exists( $email_id );

		if ( $new_post_id <= 0 ) {
			return new WP_REST_Response(
				array( 'error' => "Failed to regenerate woo_email post for {$email_id}" ),
				500
			);
		}

		return new WP_REST_Response( array( 'post_id' => $new_post_id ), 200 );
	}

	/**
	 * Apply arbitrary meta + post column updates to a post in one round-trip.
	 * Body is JSON `{ meta?: {key: value | null}, post?: {wp_update_post fields} }`.
	 *
	 * @param WP_REST_Request $request The REST request. Expects `post_id` route parameter and JSON body.
	 * @return WP_REST_Response
	 */
	public function seed_meta( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$body    = $request->get_json_params();

		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( array( 'error' => 'Body must be a JSON object' ), 400 );
		}

		if ( ! get_post( $post_id ) ) {
			return new WP_REST_Response( array( 'error' => "Post {$post_id} not found" ), 404 );
		}

		$meta_updates = $body['meta'] ?? array();
		if ( is_array( $meta_updates ) ) {
			foreach ( $meta_updates as $key => $value ) {
				if ( null === $value ) {
					delete_post_meta( $post_id, (string) $key );
				} else {
					update_post_meta( $post_id, (string) $key, $value );
				}
			}
		}

		$post_updates = $body['post'] ?? array();
		if ( is_array( $post_updates ) && ! empty( $post_updates ) ) {
			wp_update_post(
				array_merge( array( 'ID' => $post_id ), $post_updates ),
				true
			);
		}

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
				'meta'    => get_post_meta( $post_id ),
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
