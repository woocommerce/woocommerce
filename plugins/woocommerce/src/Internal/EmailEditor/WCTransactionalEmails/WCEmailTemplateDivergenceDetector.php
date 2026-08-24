<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
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
	 * Cached classification of "does the post currently differ from the
	 * canonical core render?". Computed by {@see self::classify_post()}
	 * and written by {@see self::reclassify()}; read by surfaces that
	 * filter posts by state (auto-applier targeting, list-page status
	 * column, sweep optimisation).
	 *
	 * **Single writer.** All code paths that mutate `post_content` for a
	 * `woo_email` post — auto-applier, selective applier, undo — call
	 * `reclassify()` after the write. Direct `update_post_meta()` against
	 * this key from any other call site will desync the cache.
	 *
	 * **Not the banner / indicator signal.** Whether the merchant has
	 * reviewed the latest core update is a separate question; that
	 * answer lives on {@see self::VERSION_META_KEY}.
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
	 * The canonical core version the merchant most recently reviewed for
	 * this post — written by every applier path that runs as a deliberate
	 * merchant action (auto-apply, selective apply with any choices,
	 * reset-to-default).
	 *
	 * **Load-bearing for the "update available" indicator.** RSM-141's
	 * editor banner and list-page filter chip compare this value against
	 * {@see WCEmailTemplateSyncRegistry}'s current canonical version for
	 * the email type:
	 *
	 * ```
	 * $reviewed = (string) get_post_meta( $post_id, self::VERSION_META_KEY, true );
	 * $current  = (string) ( $sync_registry[ $email_id ]['version'] ?? '' );
	 * $show_indicator = $current !== '' && version_compare( $reviewed, $current, '<' );
	 * ```
	 *
	 * Distinct from {@see self::STATUS_META_KEY}: the merchant can
	 * deliberately choose `keep_yours` for every conflict (post still
	 * differs from canonical → STATUS = core_updated_customized) and
	 * still have addressed the update for this version (VERSION = current
	 * → no indicator). Direct merchant edits to post_content do not
	 * advance this stamp; only review-driven applies do.
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
	 * Informational flag intended to be set to `true` by the RSM-149 backfill
	 * on every pre-existing `woo_email` post it stamps. Registered + surfaced
	 * read-only over REST here so RSM-145 Tracks instrumentation can distinguish
	 * backfilled posts from natively generated ones, but the writer is staged
	 * in a separate follow-up PR after the 10.8 feature freeze for the backfill
	 * class. Until then, the field defaults to `false` everywhere — Tracks will
	 * report `was_backfilled: false` for all posts. Safe default; no behavior
	 * depends on it being `true`.
	 *
	 * @var string
	 */
	public const BACKFILLED_META_KEY = '_wc_email_backfilled';

	/**
	 * Post meta key for the canonical core render at the moment of the last
	 * system write. Used as the `base` reference in three-way diff
	 * comparisons (yours-vs-base, core-vs-base) so the engine can attribute
	 * each block's change to either the merchant or to core, eliminating the
	 * inversion guard's need to fall back to "see release notes" on
	 * heavily-customized posts.
	 *
	 * Written by every code path that mutates `post_content` for a sync-
	 * eligible `woo_email` post: the generator (initial stamp), the auto-
	 * applier and reset endpoint (wholesale writes align yours with core),
	 * the selective applier (records the new canonical the merchant just
	 * synced against), and the RSM-149 backfill (seeds for legacy posts).
	 *
	 * @var string
	 * @since 10.9.0
	 */
	public const LAST_CORE_RENDER_META_KEY = '_wc_email_template_last_core_render';

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
	 * Register the four sync-related post meta keys on the `woo_email` post type as
	 * REST-readable, server-write-only meta:
	 *
	 * - {@see self::STATUS_META_KEY} (`_wc_email_template_status`)
	 * - {@see self::VERSION_META_KEY} (`_wc_email_template_version`)
	 * - {@see self::SOURCE_HASH_META_KEY} (`_wc_email_template_source_hash`)
	 * - {@see self::BACKFILLED_META_KEY} (`_wc_email_backfilled`)
	 *
	 * Because the `woo_email` post type declares `'custom-fields'` support (see
	 * {@see Integration::add_email_post_type()}), WP core auto-surfaces every
	 * `show_in_rest = true` meta key under the standard `meta` property of the
	 * `wp/v2/woo_email` response — no custom REST field registration is needed.
	 *
	 * This is a stable read contract for the email list UI, the RSM-141 editor
	 * "update available" banner, and the RSM-145 Tracks instrumentation. Renaming
	 * or removing any of these meta keys, or changing the meaning of an existing
	 * status string value, is a breaking change. Vocabulary expansion (adding new
	 * status values) is fine.
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

		register_post_meta(
			'woo_email',
			self::SOURCE_HASH_META_KEY,
			array(
				'type'              => 'string',
				'description'       => 'SHA-1 stamp of the canonical core post content recorded the last time this email post was generated, applied, or reset. Consumed by RSM-145 Tracks instrumentation to fingerprint the baseline the merchant was reviewing. Read-only over REST.',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'rest_meta_auth_read_only' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_post_meta(
			'woo_email',
			self::BACKFILLED_META_KEY,
			array(
				'type'              => 'boolean',
				'description'       => 'True when the post was stamped by the RSM-149 backfill rather than created natively by the modern generator. Consumed by RSM-145 Tracks instrumentation to segment update-banner interactions on backfilled posts. Read-only over REST.',
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => array( self::class, 'rest_meta_auth_read_only' ),
				'sanitize_callback' => 'rest_sanitize_boolean',
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
	 * Stamp {@see self::BACKFILL_COMPLETE_OPTION} on fresh WooCommerce installs.
	 *
	 * The RSM-149 backfill runs as a 10.8 db-update migration callback. Fresh
	 * installs on 10.9 (or any later release) never cross the 10.8 db-update
	 * boundary — {@see \WC_Install::needs_db_update()} short-circuits on a null
	 * `woocommerce_db_version` — so the migration never runs and the backfill
	 * option never flips. Without this fix, {@see self::run_sweep()} would
	 * early-return on every subsequent WC upgrade for the lifetime of the site
	 * and Tracks instrumentation would be silently dead.
	 *
	 * A fresh install has no legacy `woo_email` posts to backfill: every post
	 * the generator creates is already 10.9-stamped at creation. The migration
	 * is trivially complete; recording that here is the truthful statement.
	 *
	 * Hooked on `woocommerce_newly_installed`, the WP-style public action
	 * contract WC fires from {@see \WC_Install::newly_installed()} after the
	 * fresh-install flag flips.
	 *
	 * @return void
	 *
	 * @since 10.9.0
	 */
	public static function mark_backfill_complete_on_fresh_install(): void {
		update_option( self::BACKFILL_COMPLETE_OPTION, 'yes' );
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

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();

		foreach ( $registry as $email_id => $_config ) {
			try {
				$post = $posts_manager->get_email_post( (string) $email_id );
				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				self::reclassify( (int) $post->ID );
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
	 * Run the divergence classifier on a single post and stamp the resulting
	 * status. Used both by {@see self::run_sweep()} and by every code path
	 * that mutates `post_content` for a `woo_email` post (auto-applier,
	 * selective applier, undo). Centralising the write here keeps
	 * {@see self::STATUS_META_KEY} consistent regardless of which writer
	 * triggered the change.
	 *
	 * Returns the stamped status, or `null` when the classifier cannot
	 * decide (no stored source hash yet, post / email cannot be resolved).
	 * In the `null` case the meta is left untouched.
	 *
	 * @param int $post_id The `woo_email` post ID.
	 *
	 * @return string|null One of the STATUS_* constants, or null when no
	 *                     decision was made.
	 *
	 * @since 10.9.0
	 */
	public static function reclassify( int $post_id ): ?string {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || Integration::EMAIL_POST_TYPE !== $post->post_type ) {
			return null;
		}

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$email_id      = (string) $posts_manager->get_email_type_from_post_id( $post_id );
		if ( '' === $email_id ) {
			return null;
		}

		$canonical_emails = $posts_manager->get_emails_by_id();
		$email            = $canonical_emails[ $email_id ] ?? null;
		if ( ! $email instanceof \WC_Email ) {
			return null;
		}

		$stored_source_hash = (string) get_post_meta( $post_id, self::SOURCE_HASH_META_KEY, true );
		if ( '' === $stored_source_hash ) {
			// This should not normally occur post-backfill: the generator always stamps
			// this meta and RSM-149 is supposed to have backfilled pre-existing posts.
			// Surface at warning so it's visible in the default WC log UI without
			// requiring operators to lower the email-editor logging threshold.
			self::get_logger()->warning(
				sprintf(
					'Email template divergence reclassify skipped post %d for email "%s": no stored source hash.',
					$post_id,
					$email_id
				),
				array(
					'email_id' => $email_id,
					'post_id'  => $post_id,
					'context'  => 'email_template_divergence_detector',
				)
			);
			return null;
		}

		$status = self::classify_post(
			$post_id,
			$email,
			array(
				'post_content'       => (string) $post->post_content,
				'stored_source_hash' => $stored_source_hash,
			)
		);

		if ( null === $status ) {
			return null;
		}

		// Fire `_update_available` on every sweep where the merchant hasn't yet
		// reviewed the current registry version, *before* the idempotency early-
		// return below. A post that stays `core_updated_customized` across
		// multiple core releases (merchant sits on the divergence through 10.7,
		// 10.8, 10.9…) still represents a fresh "update available" signal at
		// each new `version_to`: the status meta is unchanged but the registry
		// version has advanced, so analytics should see one event per release
		// boundary. The per-`(post_id, version_to)` dedup transient in the
		// tracker prevents same-release re-fires; the suppress-during-backfill
		// gate lives there too. Order matters: we fire here rather than after
		// the meta write so the cross-release case isn't accidentally
		// short-circuited by the status-unchanged guard.
		if ( self::STATUS_CORE_UPDATED_CUSTOMIZED === $status ) {
			$sync_config  = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_id );
			$version_to   = null !== $sync_config ? (string) ( $sync_config['version'] ?? '' ) : '';
			$version_from = (string) get_post_meta( $post_id, self::VERSION_META_KEY, true );

			if ( '' !== $version_to && version_compare( $version_from, $version_to, '<' ) ) {
				WCEmailTemplateSyncTracker::record_update_available( $post_id );
			}
		}

		// Idempotent write: skip the meta call entirely when the status hasn't shifted,
		// so successive reclassifies on unchanged state produce zero DB writes (and zero
		// `update_post_metadata` filter calls observed by tests / observers).
		$existing_status = (string) get_post_meta( $post_id, self::STATUS_META_KEY, true );
		if ( $existing_status === $status ) {
			return $status;
		}

		update_post_meta( $post_id, self::STATUS_META_KEY, $status );

		return $status;
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
