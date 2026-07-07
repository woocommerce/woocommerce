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
 * Health endpoint is open; every other route requires manage_options. The plugin's location
 * under tests/e2e/test-plugins/ — only mounted via .wp-env.test.json for the test environment —
 * provides the second layer of defense.
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
				'permission_callback' => array( self::class, 'require_admin' ),
				'args'                => array(
					'email_id' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/seed-meta/(?P<post_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'seed_meta' ),
					'permission_callback' => array( self::class, 'require_admin' ),
					'args'                => array(
						'post_id' => array( 'sanitize_callback' => 'absint' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'read_meta' ),
					'permission_callback' => array( self::class, 'require_admin' ),
					'args'                => array(
						'post_id' => array( 'sanitize_callback' => 'absint' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/canonical-hash/(?P<email_id>[a-z0-9_]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'canonical_hash' ),
				'permission_callback' => array( self::class, 'require_admin' ),
				'args'                => array(
					'email_id' => array( 'sanitize_callback' => 'sanitize_key' ),
					'mode'     => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/post-content/(?P<post_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'read_post_content' ),
				'permission_callback' => array( self::class, 'require_admin' ),
				'args'                => array(
					'post_id' => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/seed-bulk',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'seed_bulk' ),
				'permission_callback' => array( self::class, 'require_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/trigger-sweep',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trigger_sweep' ),
				'permission_callback' => array( self::class, 'require_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/trigger-backfill',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trigger_backfill' ),
				'permission_callback' => array( self::class, 'require_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/tracks',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tracks' ),
					'permission_callback' => array( self::class, 'require_admin' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'clear_tracks' ),
					'permission_callback' => array( self::class, 'require_admin' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/set-option',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_option' ),
				'permission_callback' => array( self::class, 'require_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/delete-option',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'delete_option_value' ),
				'permission_callback' => array( self::class, 'require_admin' ),
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
	 * Timestamp columns (`post_date`, `post_date_gmt`, `post_modified`,
	 * `post_modified_gmt`) are applied via a direct `$wpdb->update()` after the
	 * `wp_update_post()` call, because WordPress always overwrites `post_modified*`
	 * with the current time during an update pass — passing them through
	 * `wp_update_post()` would be silently ignored.
	 *
	 * @param WP_REST_Request $request The REST request. Expects `post_id` route parameter and JSON body.
	 * @return WP_REST_Response
	 */
	public function seed_meta( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;

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

		// Timestamp columns must be handled separately: wp_update_post always
		// stamps post_modified* with current_time(), ignoring any caller-supplied
		// value. Split the post update array into "regular" fields (handled by
		// wp_update_post) and timestamp fields (applied via direct DB write after).
		$timestamp_columns = array( 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt' );

		$post_updates      = $body['post'] ?? array();
		$timestamp_updates = array();
		$regular_updates   = array();

		if ( is_array( $post_updates ) ) {
			foreach ( $post_updates as $col => $value ) {
				if ( in_array( $col, $timestamp_columns, true ) ) {
					$timestamp_updates[ $col ] = (string) $value;
				} else {
					$regular_updates[ $col ] = $value;
				}
			}
		}

		if ( ! empty( $regular_updates ) ) {
			wp_update_post(
				array_merge( array( 'ID' => $post_id ), $regular_updates ),
				true
			);
		}

		if ( ! empty( $timestamp_updates ) ) {
			// Ensure the local-time columns stay consistent with their GMT counterparts.
			// `was_never_edited()` in WCEmailTemplateSyncBackfill checks the local pair
			// as a fallback (`post_date === post_modified`), so both pairs must differ
			// for the post to be classified as Case C rather than Case B.
			if ( isset( $timestamp_updates['post_date_gmt'] ) && ! isset( $timestamp_updates['post_date'] ) ) {
				$timestamp_updates['post_date'] = get_date_from_gmt( $timestamp_updates['post_date_gmt'] );
			}
			if ( isset( $timestamp_updates['post_modified_gmt'] ) && ! isset( $timestamp_updates['post_modified'] ) ) {
				$timestamp_updates['post_modified'] = get_date_from_gmt( $timestamp_updates['post_modified_gmt'] );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->posts,
				$timestamp_updates,
				array( 'ID' => $post_id ),
				array_fill( 0, count( $timestamp_updates ), '%s' ),
				array( '%d' )
			);
			clean_post_cache( $post_id );
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
	 * Read all post meta for a post. Mirrors seed-meta's response shape (without writes).
	 *
	 * @param WP_REST_Request $request The REST request. Expects `post_id` route parameter.
	 * @return WP_REST_Response
	 */
	public function read_meta( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! get_post( $post_id ) ) {
			return new WP_REST_Response( array( 'error' => "Post {$post_id} not found" ), 404 );
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
	 * Read the raw post_content for a post. Bypasses the wp/v2 REST surface, which
	 * doesn't reliably expose `content.raw` for custom post types under all auth modes.
	 *
	 * @param WP_REST_Request $request The REST request. Expects `post_id` route parameter.
	 * @return WP_REST_Response
	 */
	public function read_post_content( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_REST_Response( array( 'error' => "Post {$post_id} not found" ), 404 );
		}

		return new WP_REST_Response(
			array(
				'post_id'      => $post_id,
				'post_content' => (string) $post->post_content,
			),
			200
		);
	}

	/**
	 * Compute the sha1 of the canonical core HTML for a given email type. Mode 'old'
	 * temporarily suppresses the Template_HTML_Overrides option so the canonical
	 * resolves to the real WC default; mode 'current' applies the active override
	 * (if any).
	 *
	 * @param WP_REST_Request $request The REST request. Expects `email_id` and `mode` params.
	 * @return WP_REST_Response
	 */
	public function canonical_hash( WP_REST_Request $request ): WP_REST_Response {
		$email_id = (string) $request->get_param( 'email_id' );
		$mode     = (string) $request->get_param( 'mode' );

		$manager = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager::get_instance();
		$email   = $manager->get_email_by_id( $email_id );
		if ( ! $email instanceof \WC_Email ) {
			return new WP_REST_Response( array( 'error' => "Unknown email_id {$email_id}" ), 404 );
		}

		$existing_override = get_option( Template_HTML_Overrides::OPTION_NAME, array() );
		if ( 'old' === $mode ) {
			delete_option( Template_HTML_Overrides::OPTION_NAME );
		}

		$canonical = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		if ( 'old' === $mode && is_array( $existing_override ) && ! empty( $existing_override ) ) {
			update_option( Template_HTML_Overrides::OPTION_NAME, $existing_override, false );
		}

		return new WP_REST_Response(
			array(
				'hash'      => sha1( $canonical ),
				'canonical' => $canonical,
			),
			200
		);
	}

	/**
	 * Bulk-insert woo_email posts with seeded meta in one round-trip. Body shape:
	 * `{ "seeds": [ { "post": <wp_insert_post args>, "meta": {key: value | null, ...} }, ... ] }`.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function seed_bulk( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) || empty( $body['seeds'] ) || ! is_array( $body['seeds'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Body must include a "seeds" array' ), 400 );
		}

		$results = array();
		foreach ( $body['seeds'] as $seed ) {
			$post_data = array_merge(
				array(
					'post_type'   => 'woo_email',
					'post_status' => 'publish',
				),
				is_array( $seed['post'] ?? null ) ? $seed['post'] : array()
			);

			$post_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				$results[] = array( 'error' => $post_id->get_error_message() );
				continue;
			}

			$meta_updates = $seed['meta'] ?? array();
			if ( is_array( $meta_updates ) ) {
				foreach ( $meta_updates as $key => $value ) {
					if ( null === $value ) {
						delete_post_meta( (int) $post_id, (string) $key );
					} else {
						update_post_meta( (int) $post_id, (string) $key, $value );
					}
				}
			}

			$results[] = array( 'post_id' => (int) $post_id );
		}

		return new WP_REST_Response( array( 'results' => $results ), 200 );
	}

	/**
	 * Run the divergence sweep inline, then immediately run the auto-applier inline
	 * (bypassing Action Scheduler), then snapshot classifications from post meta
	 * across all sync-enabled emails.
	 *
	 * Production flow: run_sweep() fires the sweep_complete action → schedule()
	 * enqueues an async AS job → run() executes later. For E2E tests we need the
	 * full classify-then-apply cycle to complete within the same HTTP request so
	 * assertions can run immediately after this call returns.
	 *
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return WP_REST_Response
	 */
	public function trigger_sweep( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector::run_sweep();

		// Run the auto-applier inline so unmodified posts are stamped IN_SYNC before
		// this response returns. In production the applier is deferred via Action
		// Scheduler; calling run() directly here keeps the E2E request synchronous.
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateAutoApplier::run();

		$registry = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$manager  = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager::get_instance();

		$classifications = array();
		foreach ( array_keys( $registry ) as $email_id ) {
			$post = $manager->get_email_post( (string) $email_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$status = (string) get_post_meta( (int) $post->ID, \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector::STATUS_META_KEY, true );
			if ( '' !== $status ) {
				$classifications[ (int) $post->ID ] = $status;
			}
		}

		return new WP_REST_Response(
			array(
				'touched'         => count( $classifications ),
				'classifications' => $classifications,
			),
			200
		);
	}

	/**
	 * Run the legacy-post backfill inline, then snapshot a count of stamped posts.
	 * Production `run()` returns false unconditionally — value reported back is for caller
	 * convenience only. Tests assert via meta snapshots before/after.
	 *
	 * WC_Tracks::record_event() short-circuits when site tracking is disabled (the
	 * default in wp-env), so it never reaches the woocommerce_tracks_event_properties
	 * filter that Tracks_Recorder hooks into. To capture the _backfill_completed event
	 * reliably we inject a custom recorder via set_event_recorder() that writes
	 * directly to the Tracks_Recorder log option, then restore the default (null)
	 * after the backfill completes.
	 *
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return WP_REST_Response
	 */
	public function trigger_backfill( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		// Inject a direct-write recorder so _backfill_completed lands in the log
		// even when WC_Tracks::record_event() is disabled for the test environment.
		// The recorder is gated on the same wc_test_tracks_enabled option that
		// Tracks_Recorder uses, so it only fires when a spy is active and the log
		// stays empty for tests that don't attach a spy.
		//
		// Event names get the 'wcadmin_' prefix to match WC_Tracks::PREFIX — that's
		// what server-side events are dispatched as via WC_Tracks::record_event().
		// The TRACKS_EVENTS constants in classifications.ts expect the prefixed name.
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder(
			static function ( string $event_name, array $payload ): void {
				if ( 'yes' !== get_option( Tracks_Recorder::ENABLED_OPTION, 'no' ) ) {
					return;
				}
				$log = get_option( Tracks_Recorder::LOG_OPTION, array() );
				if ( ! is_array( $log ) ) {
					$log = array();
				}
				$log[] = array(
					'name'         => 'wcadmin_' . $event_name,
					'properties'   => $payload,
					'timestamp_ms' => (int) ( microtime( true ) * 1000 ),
				);
				update_option( Tracks_Recorder::LOG_OPTION, $log, false );
			}
		);

		$ran = \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncBackfill::run();

		// Restore the default recorder so subsequent calls don't double-log via
		// both the injected recorder and any future WC_Tracks path.
		\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateSyncTracker::set_event_recorder( null );

		global $wpdb;
		$stamped = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> ''",
				\Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY
			)
		);

		return new WP_REST_Response(
			array(
				'ran'     => (bool) $ran,
				'stamped' => $stamped,
			),
			200
		);
	}

	/**
	 * Return the in-option Tracks event log captured by Tracks_Recorder.
	 *
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return WP_REST_Response
	 */
	public function get_tracks( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		$log = get_option( Tracks_Recorder::LOG_OPTION, array() );
		return new WP_REST_Response(
			array( 'events' => is_array( $log ) ? $log : array() ),
			200
		);
	}

	/**
	 * Clear the Tracks event log option so the next read starts empty.
	 *
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return WP_REST_Response
	 */
	public function clear_tracks( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		delete_option( Tracks_Recorder::LOG_OPTION );
		return new WP_REST_Response( array( 'cleared' => true ), 200 );
	}

	/**
	 * Write a typed value to a WordPress option, preserving array/object structure.
	 * Body shape: `{ "option_name": string, "option_value": mixed }`. Unlike the
	 * shared `e2e-options/update` endpoint, this preserves arrays and nested objects
	 * because it pulls the value from the JSON body rather than sanitize_text_field.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function set_option( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) || ! isset( $body['option_name'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Body must include option_name' ), 400 );
		}

		$option_name  = (string) $body['option_name'];
		$option_value = $body['option_value'] ?? '';

		update_option( $option_name, $option_value, false );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Delete a WordPress option. Body shape: `{ "option_name": string }`.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response
	 */
	public function delete_option_value( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) || ! isset( $body['option_name'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Body must include option_name' ), 400 );
		}

		delete_option( (string) $body['option_name'] );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Permission callback used by every non-health endpoint. Requires the manage_options
	 * capability. The plugin is only mounted in test environments via .wp-env.test.json — it
	 * does not ship in any production WooCommerce build — which provides the second
	 * layer of defense.
	 *
	 * @param WP_REST_Request $request The REST request (unused).
	 * @return bool
	 */
	public static function require_admin( WP_REST_Request $request ): bool {
		unset( $request );
		return current_user_can( 'manage_options' );
	}
}
