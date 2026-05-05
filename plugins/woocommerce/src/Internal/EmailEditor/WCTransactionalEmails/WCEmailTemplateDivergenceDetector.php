<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Detects divergence between generated `woo_email` posts and their source block
 * email templates after WooCommerce is upgraded.
 *
 * For every sync-enabled email (see {@see WCEmailTemplateSyncRegistry}) that has
 * a generated post carrying the `_wc_email_template_source_hash` stamp written by
 * {@see WCTransactionalEmailPostsGenerator}, this class recomputes the current
 * core and current post hashes and classifies the post into one of:
 *
 * - `in_sync`                    — core and post still match the stamped baseline.
 * - `core_updated_uncustomized`  — core changed but the post kept pace (no merchant edits).
 * - `core_updated_customized`    — core changed and the post diverges (merchant customisations).
 *
 * The classification is persisted on the post's `_wc_email_template_status` meta
 * so downstream UI can surface an accurate status. The sweep is idempotent: runs
 * with unchanged state write zero rows.
 *
 * Hash input parity with the stamping path is guaranteed by construction because
 * both paths route through {@see WCTransactionalEmailPostsGenerator::compute_canonical_post_content()}.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 10.8.0
 */
class WCEmailTemplateDivergenceDetector {
	/**
	 * Option written by the WCEmailTemplateSyncBackfill backfill to signal that every existing `woo_email`
	 * post has been stamped with the RSM-137 sync meta. The detector refuses to run
	 * until this flag flips to `yes` — otherwise legacy posts would be evaluated with
	 * no stored hash and silently skipped, giving a misleadingly quiet sweep.
	 *
	 * @var string
	 */
	public const BACKFILL_COMPLETE_OPTION = 'woocommerce_email_template_sync_backfill_complete';

	/**
	 * Post meta key the detector writes.
	 *
	 * @var string
	 */
	public const STATUS_META_KEY = '_wc_email_template_status';

	/**
	 * Post meta key written by the generator; required for classification.
	 *
	 * @var string
	 */
	public const SOURCE_HASH_META_KEY = '_wc_email_template_source_hash';

	/**
	 * Post meta key storing the version of the block template the post was stamped
	 * against. Written by the generator and by the RSM-149 backfill.
	 *
	 * @var string
	 */
	public const VERSION_META_KEY = '_wc_email_template_version';

	/**
	 * Post meta key storing the UTC timestamp (Y-m-d H:i:s) of the last sync stamp.
	 * Written by the generator and by the RSM-149 backfill.
	 *
	 * @var string
	 */
	public const LAST_SYNCED_AT_META_KEY = '_wc_email_last_synced_at';

	/**
	 * Classification outcomes.
	 */
	public const STATUS_IN_SYNC                   = 'in_sync';
	public const STATUS_CORE_UPDATED_UNCUSTOMIZED = 'core_updated_uncustomized';
	public const STATUS_CORE_UPDATED_CUSTOMIZED   = 'core_updated_customized';

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static $logger = null;

	/**
	 * Register `_wc_email_template_status` and `_wc_email_template_version` post meta on
	 * the `woo_email` post type as REST-readable, server-write-only meta.
	 *
	 * Because the `woo_email` post type declares `'custom-fields'` support (see
	 * {@see Integration::add_email_post_type()}), WP core auto-surfaces every
	 * `show_in_rest = true` meta key under the standard `meta` property of the
	 * `wp/v2/woo_email` response — no custom REST field registration is needed.
	 *
	 * This is a stable read contract for the email list UI and any downstream consumer
	 * (extensions, headless admins). Renaming or removing either meta key, or changing
	 * the meaning of an existing status string value, is a breaking change. Vocabulary
	 * expansion (adding new status values) is fine.
	 *
	 * Hook: `init`.
	 *
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function register_meta(): void {
		register_post_meta(
			'woo_email',
			self::STATUS_META_KEY,
			array(
				'type'              => 'string',
				'description'       => 'Classification of this email post relative to the current core template ("in_sync", "core_updated_uncustomized", or "core_updated_customized"). Written server-side by the divergence detector and apply / reset flows; read-only over REST.',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'rest_meta_auth_read_only' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			'woo_email',
			self::VERSION_META_KEY,
			array(
				'type'              => 'string',
				'description'       => 'Core template version stamp recorded the last time this email post was generated, applied, or reset. Read-only over REST.',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'rest_meta_auth_read_only' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * REST auth gate for `_wc_email_template_*` meta.
	 *
	 * - Read: allowed for users who can edit `woo_email` posts (matches the email-list capability).
	 * - Write: never allowed via REST. Meta is owned by server-side detection, apply, and reset flows.
	 *
	 * Signature follows the `auth_{$object_type}_meta_{$meta_key}` filter contract.
	 *
	 * @param bool   $allowed   Whether the request is allowed (current state).
	 * @param string $meta_key  The meta key in question.
	 * @param int    $object_id The post ID.
	 * @param int    $user_id   The current user ID.
	 * @param string $cap       The capability being requested.
	 * @param array  $caps      The full set of caps the user must have.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function rest_meta_auth_read_only( $allowed, $meta_key, $object_id, $user_id, $cap, $caps ): bool {
		unset( $allowed, $meta_key, $caps );

		// Block all writes regardless of caller.
		if ( in_array( $cap, array( 'edit_post_meta', 'add_post_meta', 'delete_post_meta' ), true ) ) {
			return false;
		}
		// For reads, defer to whether the user can edit the post.
		return user_can( $user_id, 'edit_post', $object_id );
	}

	/**
	 * Run the post-upgrade divergence sweep.
	 *
	 * Intended to be hooked on `woocommerce_updated`, which fires once per WC
	 * upgrade inside {@see \WC_Install::check_version()} under a distributed
	 * install lock — that guarantees the sweep runs at most once per upgrade
	 * without any additional fence option or cache lock on our side.
	 *
	 * Early-returns when the RSM-149 backfill has not yet flagged completion, so
	 * we never classify a half-populated set of posts.
	 *
	 * @return void
	 *
	 * @since 10.8.0
	 */
	public static function run_sweep(): void {
		if ( 'yes' !== get_option( self::BACKFILL_COMPLETE_OPTION ) ) {
			return;
		}

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		if ( empty( $registry ) ) {
			return;
		}

		$posts_manager    = WCTransactionalEmailPostsManager::get_instance();
		$canonical_emails = $posts_manager->get_emails_by_id();

		foreach ( $registry as $email_id => $_config ) {
			try {
				$email = $canonical_emails[ $email_id ] ?? null;
				if ( ! $email instanceof \WC_Email ) {
					// Extension providing the email was deactivated; nothing to classify.
					continue;
				}

				$post = $posts_manager->get_email_post( (string) $email_id );
				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				$stored_source_hash = (string) get_post_meta( (int) $post->ID, self::SOURCE_HASH_META_KEY, true );
				if ( '' === $stored_source_hash ) {
					// This should not normally occur post-backfill: the generator always stamps
					// this meta and RSM-149 is supposed to have backfilled pre-existing posts.
					// Surface at warning so it's visible in the default WC log UI without
					// requiring operators to lower the email-editor logging threshold.
					self::get_logger()->warning(
						sprintf(
							'Email template divergence sweep skipped post %d for email "%s": no stored source hash.',
							(int) $post->ID,
							(string) $email_id
						),
						array(
							'email_id' => (string) $email_id,
							'post_id'  => (int) $post->ID,
							'context'  => 'email_template_divergence_detector',
						)
					);
					continue;
				}

				$status = self::classify_post(
					(int) $post->ID,
					$email,
					array(
						'post_content'       => (string) $post->post_content,
						'stored_source_hash' => $stored_source_hash,
					)
				);

				if ( null === $status ) {
					continue;
				}

				$existing_status = (string) get_post_meta( (int) $post->ID, self::STATUS_META_KEY, true );
				if ( $existing_status === $status ) {
					continue;
				}

				update_post_meta( (int) $post->ID, self::STATUS_META_KEY, $status );
			} catch ( \Throwable $e ) {
				self::get_logger()->error(
					sprintf(
						'Email template divergence sweep failed for email "%s": %s',
						(string) $email_id,
						$e->getMessage()
					),
					array(
						'email_id' => (string) $email_id,
						'context'  => 'email_template_divergence_detector',
					)
				);
				continue;
			}//end try
		}//end foreach

		/**
		 * Fires once after the post-upgrade divergence sweep finishes classifying
		 * every sync-registered email post.
		 *
		 * Hooked by {@see \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateAutoApplier::schedule()}
		 * to enqueue the batched auto-apply job for posts classified as
		 * `core_updated_uncustomized`. Fires unconditionally — auto-applier
		 * short-circuits when no candidates exist.
		 *
		 * @since 10.8.0
		 */
		do_action( 'woocommerce_email_template_divergence_sweep_complete' );
	}

	/**
	 * Classify a single generated `woo_email` post.
	 *
	 * Pure function: given the inputs, always returns the same outcome.
	 *
	 * Classification hinges on two independent questions:
	 *   1. Has core moved since we stamped the post? (`currentCoreHash !== storedSourceHash`)
	 *   2. Has the merchant edited the post since we stamped it? (`currentPostHash !== storedSourceHash`)
	 *
	 * Note that "uncustomized" here means the merchant has **not** edited the post, which
	 * is detected by `currentPostHash === storedSourceHash` — NOT by comparing against the
	 * new core hash. The latter would only hold after an auto-apply step which this code
	 * path does not perform.
	 *
	 * Returns `null` when the stored baseline is ambiguous — i.e. core has not moved but
	 * the post has drifted from the stamp. In that case the existing status is preserved
	 * rather than overwritten with a new guess.
	 *
	 * @param int       $post_id The post ID (kept in the signature for context in tests and logs).
	 * @param \WC_Email $email   The registered email instance.
	 * @param array     $stamps  Map with keys `post_content` (current persisted content) and
	 *                           `stored_source_hash` (value of `_wc_email_template_source_hash`).
	 * @return string|null One of the STATUS_* constants, or null when the status should not be updated.
	 *
	 * @since 10.8.0
	 */
	public static function classify_post( int $post_id, \WC_Email $email, array $stamps ): ?string {
		// $post_id is surfaced in the signature for future instrumentation and log context; no current use.
		unset( $post_id );

		$current_core_hash  = sha1( WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ) );
		$current_post_hash  = sha1( (string) ( $stamps['post_content'] ?? '' ) );
		$stored_source_hash = (string) ( $stamps['stored_source_hash'] ?? '' );

		if ( ! self::is_sha1_hash( $stored_source_hash ) ) {
			return null;
		}

		// Core has not moved since stamping. If the post also matches the stamp we're in sync;
		// otherwise the merchant drifted without a core update — ambiguous, leave prior status.
		if ( $current_core_hash === $stored_source_hash ) {
			return $current_post_hash === $stored_source_hash ? self::STATUS_IN_SYNC : null;
		}

		// Core has moved. Did the merchant edit the post since we stamped it?
		return $current_post_hash === $stored_source_hash
			? self::STATUS_CORE_UPDATED_UNCUSTOMIZED
			: self::STATUS_CORE_UPDATED_CUSTOMIZED;
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
	 * `_wc_email_template_source_hash` is produced by `sha1()` (40 hex chars), but as
	 * persisted post meta it is theoretically reachable from DB migrations, direct
	 * `update_post_meta` calls, or misbehaving extensions. Any non-SHA-1 value would
	 * otherwise be compared byte-for-byte against real hashes and always report
	 * `core_updated_customized`, so we short-circuit instead.
	 *
	 * @param string $hash Candidate hash value.
	 * @return bool True when the value is a 40-character hex string.
	 */
	private static function is_sha1_hash( string $hash ): bool {
		return 40 === strlen( $hash ) && ctype_xdigit( $hash );
	}
}
