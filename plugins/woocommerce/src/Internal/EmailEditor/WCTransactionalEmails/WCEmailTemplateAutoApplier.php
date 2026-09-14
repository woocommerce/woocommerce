<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Auto-applies the current core block template to `woo_email` posts that have
 * been classified `core_updated_uncustomized` by {@see WCEmailTemplateDivergenceDetector}.
 *
 * Mirrors how legacy static-file emails always reflected the latest core
 * template: when the merchant has not customised a generated post since it was
 * last stamped, we silently rewrite its content to the new core render and
 * flip its status meta back to `in_sync`.
 *
 * The per-post atom ({@see self::apply_to_post()}) is shared with the
 * `POST /woocommerce-email-editor/v1/emails/{id}/reset` endpoint
 * (see {@see \Automattic\WooCommerce\Internal\EmailEditor\EmailApiController::reset_response()})
 * so reset and auto-apply share one canonical-write code path.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 10.8.0
 */
class WCEmailTemplateAutoApplier {
	/**
	 * Action Scheduler hook name for the batched auto-apply runner.
	 *
	 * Internal AS plumbing — not intended for extension consumption. To react to
	 * the upgrade-time signal, hook {@see 'woocommerce_email_template_divergence_sweep_complete'}
	 * instead.
	 *
	 * @var string
	 */
	public const AUTO_APPLY_AS_HOOK = 'woocommerce_email_template_auto_apply_uncustomized';

	/**
	 * Action Scheduler group for the batched auto-apply runner.
	 *
	 * Internal AS plumbing — not intended for extension consumption. The
	 * `woocommerce-email-editor` namespace is reserved for the standalone email
	 * editor package; integration glue lives under
	 * `woocommerce-email-editor-integration` to keep the boundary explicit.
	 *
	 * @var string
	 */
	public const AUTO_APPLY_AS_GROUP = 'woocommerce-email-editor-integration';

	/**
	 * Re-entrancy flag set while the atom rewrites a post.
	 *
	 * Future `save_post` listeners (e.g. RSM-143's customised-post detector,
	 * RSM-145's Tracks event firing) should consult {@see self::is_auto_applying()}
	 * before interpreting a write as a merchant edit.
	 *
	 * @var bool
	 */
	private static bool $is_auto_applying = false;

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static ?Email_Editor_Logger_Interface $logger = null;

	/**
	 * Apply the current core template render to a single `woo_email` post and stamp sync meta.
	 *
	 * Two callers, two modes:
	 *   - Auto-applier (default): `$opts['require_uncustomized'] === true`. Rejects with
	 *     `WP_Error` when the post has no stored hash, has been edited since stamping,
	 *     or belongs to a non-sync-enabled email.
	 *   - Reset endpoint: `$opts['require_uncustomized'] === false`. Unconditional rewrite.
	 *     Non-sync-enabled emails receive a content-only reset and the return shape carries
	 *     `null` for the four sync fields (BC contract with the pre-RSM-139 reset endpoint).
	 *
	 * The four meta writes are skipped entirely if `wp_update_post` fails, so a
	 * `WP_Error` return leaves the post and existing meta untouched. Matches the
	 * pre-RSM-139 reset endpoint shape (see PR #64355 review on `2fa660b3b9`).
	 *
	 * @param \WC_Email $email   The transactional email instance.
	 * @param int       $post_id The post ID.
	 * @param array     $opts    Options. Recognised keys:
	 *                           - `require_uncustomized` (bool, default true): see above.
	 * @return array|\WP_Error On success, an array with keys `content`, `version`,
	 *                         `source_hash`, `synced_at`, `status`. On failure, a `WP_Error`.
	 *
	 * @since 10.8.0
	 */
	public static function apply_to_post( \WC_Email $email, int $post_id, array $opts = array() ) {
		$require_uncustomized = ! isset( $opts['require_uncustomized'] ) || (bool) $opts['require_uncustomized'];

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( (string) $email->id );

		if ( $require_uncustomized && null === $sync_config ) {
			return new \WP_Error(
				'not_sync_enabled',
				sprintf(
					/* translators: %s: email ID */
					__( 'Email "%s" is not registered for template sync.', 'woocommerce' ),
					(string) $email->id
				)
			);
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || \Automattic\WooCommerce\Internal\EmailEditor\Integration::EMAIL_POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'post_not_found',
				sprintf(
					/* translators: %d: post ID */
					__( 'No woo_email post found for ID %d.', 'woocommerce' ),
					$post_id
				)
			);
		}

		$stored_source_hash = '';
		if ( $require_uncustomized ) {
			$stored_source_hash = (string) get_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
			if ( '' === $stored_source_hash || ! self::is_sha1_hash( $stored_source_hash ) ) {
				return new \WP_Error(
					'no_stored_hash',
					sprintf(
						/* translators: %d: post ID */
						__( 'Post %d has no stored source hash; cannot safely auto-apply.', 'woocommerce' ),
						$post_id
					)
				);
			}
		}//end if

		$canonical   = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
		$source_hash = null;
		$synced_at   = null;
		$status      = null;
		$version     = null;

		self::$is_auto_applying = true;
		try {
			// Re-hash post_content immediately before the write to minimise the
			// TOCTOU gap between the snapshot and wp_update_post. The first $post
			// load above is too early — `compute_canonical_post_content` runs in
			// between and yields the window where a merchant save could otherwise
			// be silently overwritten.
			if ( $require_uncustomized ) {
				$latest_post = get_post( $post_id );
				if ( ! $latest_post instanceof \WP_Post
					|| sha1( (string) $latest_post->post_content ) !== $stored_source_hash
				) {
					return new \WP_Error(
						'post_modified_since_stamp',
						sprintf(
							/* translators: %d: post ID */
							__( 'Post %d has been modified since the last sync stamp; skipping auto-apply.', 'woocommerce' ),
							$post_id
						)
					);
				}
			}

			$updated = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $canonical,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			// Read back the persisted post_content. The `content_save_pre` filter
			// chain can mutate `$canonical` between the in-memory string and what
			// lands in the DB, so both the returned `content` field and the
			// stamped source hash must reflect what the database actually holds.
			// See the same note in `WCEmailTemplateSelectiveApplier::apply_selectively()`.
			$saved_post = get_post( $post_id );
			$saved_body = $saved_post instanceof \WP_Post ? (string) $saved_post->post_content : $canonical;
			$canonical  = $saved_body;

			if ( null !== $sync_config ) {
				$source_hash = sha1( $canonical );
				$synced_at   = gmdate( 'Y-m-d H:i:s' );
				$version     = (string) $sync_config['version'];

				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $version );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, $source_hash );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, $synced_at );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $canonical );

				// Status comes from the classifier so all writers stay consistent.
				// In this path we always write canonical, so the classifier returns
				// IN_SYNC, but going through the same helper as the selective applier
				// avoids drift if a future partial-apply path is added here.
				$status = WCEmailTemplateDivergenceDetector::reclassify( $post_id );
			}//end if
		} finally {
			self::$is_auto_applying = false;
		}//end try

		// Fire `_update_applied` for the auto-applier path. Static extensions:
		// the auto-applier only acts on `core_updated_uncustomized` posts, so
		// `had_customizations` is always false and `auto_resolved` is always true.
		// Gate on `$require_uncustomized`: this method is also reused by the
		// reset endpoint (with `require_uncustomized = false`) — the reset
		// surface is not in RSM-145's event taxonomy and must not be tagged
		// as `applied_from='auto'`.
		if ( $require_uncustomized ) {
			WCEmailTemplateSyncTracker::record_auto_applied( $post_id );
		}

		return array(
			'content'     => $canonical,
			'version'     => $version,
			'source_hash' => $source_hash,
			'synced_at'   => $synced_at,
			'status'      => $status,
		);
	}

	/**
	 * Enqueue the batched auto-apply runner as an Action Scheduler async action.
	 *
	 * Hooked to {@see 'woocommerce_email_template_divergence_sweep_complete'}. The
	 * `as_has_scheduled_action()` short-circuit guards against double-enqueueing
	 * when the detector sweep runs twice in one request — once on
	 * `woocommerce_updated`, once on `BACKFILL_COMPLETE_ACTION`.
	 *
	 * @since 10.8.0
	 */
	public static function schedule(): void {
		if ( as_has_scheduled_action( self::AUTO_APPLY_AS_HOOK, array(), self::AUTO_APPLY_AS_GROUP ) ) {
			return;
		}

		as_enqueue_async_action( self::AUTO_APPLY_AS_HOOK, array(), self::AUTO_APPLY_AS_GROUP );
	}

	/**
	 * Action Scheduler callback. Apply the canonical core render to every
	 * `woo_email` post whose status meta is `core_updated_uncustomized`.
	 *
	 * Per-post `try`/`catch` ensures one bad post never breaks the rest of the
	 * batch (acceptance criterion). Status meta is never mutated by this method
	 * on failure — the next sweep re-classifies.
	 *
	 * Declared `void` because Action Scheduler async-action callbacks discard
	 * the return value; a return type would just be noise (and trip PHPStan's
	 * `return.void` rule on `add_action`).
	 *
	 * @since 10.8.0
	 */
	public static function run(): void {
		if ( 'yes' !== get_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION ) ) {
			return;
		}

		// `post_status=any` includes draft / private / pending / future and
		// excludes trash / auto-draft / inherit — anything not in the trash bucket
		// is fair game for auto-apply.
		$candidate_ids = get_posts(
			array(
				'post_type'      => \Automattic\WooCommerce\Internal\EmailEditor\Integration::EMAIL_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array(  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded set; sync-registered emails only.
					array(
						'key'   => WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
						'value' => WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_UNCUSTOMIZED,
					),
				),
			)
		);

		if ( empty( $candidate_ids ) ) {
			return;
		}

		$registry      = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$emails_by_id  = $posts_manager->get_emails_by_id();

		foreach ( $candidate_ids as $post_id ) {
			$post_id = (int) $post_id;
			try {
				$email_id = (string) $posts_manager->get_email_type_from_post_id( $post_id );
				if ( '' === $email_id || ! isset( $registry[ $email_id ] ) ) {
					continue;
				}

				$email = $emails_by_id[ $email_id ] ?? null;
				if ( ! $email instanceof \WC_Email ) {
					continue;
				}

				$result = self::apply_to_post( $email, $post_id );

				if ( is_wp_error( $result ) ) {
					self::log_apply_error( $result, $post_id, $email_id );
				}
			} catch ( \Throwable $e ) {
				self::get_logger()->error(
					sprintf(
						'Email template auto-apply failed for post %d: %s',
						$post_id,
						$e->getMessage()
					),
					array(
						'post_id' => $post_id,
						'context' => 'email_template_auto_applier',
					)
				);
				continue;
			}//end try
		}//end foreach
	}

	/**
	 * Map a per-post `WP_Error` from {@see self::apply_to_post()} to the right
	 * log severity and emit it.
	 *
	 * `post_modified_since_stamp` is the expected race outcome (merchant edit in
	 * the AS lag window) and is logged at `info`. Everything else is at `warning`
	 * or `error` so it surfaces in the default WC log UI.
	 *
	 * @param \WP_Error $error    The error returned by apply_to_post.
	 * @param int       $post_id  Post ID being processed.
	 * @param string    $email_id Email ID being processed.
	 */
	private static function log_apply_error( \WP_Error $error, int $post_id, string $email_id ): void {
		$message = sprintf(
			'Email template auto-apply skipped post %d for email "%s": %s',
			$post_id,
			$email_id,
			$error->get_error_message()
		);

		$context = array(
			'post_id'  => $post_id,
			'email_id' => $email_id,
			'context'  => 'email_template_auto_applier',
		);

		switch ( $error->get_error_code() ) {
			case 'post_modified_since_stamp':
				self::get_logger()->info( $message, $context );
				return;
			case 'no_stored_hash':
			case 'not_sync_enabled':
				self::get_logger()->warning( $message, $context );
				return;
			default:
				self::get_logger()->error( $message, $context );
		}
	}

	/**
	 * Whether the auto-applier is currently rewriting a post.
	 *
	 * Future `save_post` listeners (RSM-143, RSM-145) should consult this flag
	 * to differentiate merchant edits from system-initiated writes.
	 *
	 * @return bool
	 *
	 * @since 10.8.0
	 */
	public static function is_auto_applying(): bool {
		return self::$is_auto_applying;
	}

	/**
	 * Override the logger implementation. Intended for tests only.
	 *
	 * @internal
	 *
	 * @param Email_Editor_Logger_Interface|null $logger The logger implementation, or null to restore the default.
	 */
	public static function set_logger( ?Email_Editor_Logger_Interface $logger ): void {
		self::$logger = $logger;
	}

	/**
	 * Return the logger instance, lazily creating it the first time.
	 *
	 * @return Email_Editor_Logger_Interface
	 */
	private static function get_logger(): Email_Editor_Logger_Interface {
		if ( null === self::$logger ) {
			self::$logger = new Logger( wc_get_logger() );
		}

		return self::$logger;
	}

	/**
	 * Validate that a string is shaped like a SHA-1 hex digest.
	 *
	 * @param string $hash Candidate hash value.
	 * @return bool True when the value is a 40-character hex string.
	 */
	private static function is_sha1_hash( string $hash ): bool {
		return 40 === strlen( $hash ) && ctype_xdigit( $hash );
	}
}
