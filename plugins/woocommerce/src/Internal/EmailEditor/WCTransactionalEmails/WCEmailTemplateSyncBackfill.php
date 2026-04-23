<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * Backfills sync meta onto pre-existing `woo_email` posts so the divergence
 * detector introduced by RSM-138 can classify legacy installs safely.
 *
 * Runs once per site as part of WooCommerce's standard db-updates pipeline
 * (see {@see \WC_Install::$db_updates}). The `woocommerce_db_version` fence
 * guarantees the migration fires exactly once; Action Scheduler handles the
 * async dispatch. A single synchronous pass is sufficient — the registered
 * post set is bounded (core + opted-in third parties), not a user-generated
 * dataset — so we deliberately avoid batching.
 *
 * For every eligible post (woo_email, not trashed, no stored source hash) the
 * callback classifies the post into one of three cases and stamps the four
 * sync meta keys plus an initial status:
 *
 * - **Case A** — post content already matches the current canonical core
 *   render. Stamp only; status `in_sync`.
 * - **Case B** — content differs from core but the post has never been edited
 *   (`post_date_gmt === post_modified_gmt`). Rewrite `post_content` to the
 *   canonical render, then stamp; status `in_sync`.
 * - **Case C** — content differs from core and the post has been edited.
 *   Stamp only using the current core hash (never `sha1(post_content)` —
 *   that would misclassify as `core_updated_uncustomized` on the next core
 *   bump). Status is seeded to `core_updated_customized` because
 *   {@see WCEmailTemplateDivergenceDetector::classify_post()} returns `null`
 *   for the "merchant drift, no core move" state and Case C is by definition
 *   customized content relative to current core.
 *
 * When Case B auto-apply lands (tracked in RSM-139), the content-rewrite
 * block here can be extracted into a shared helper; the `is_backfilling()`
 * flag is a forward hook for that listener.
 *
 * Finalization is a two-step handshake:
 *  1. Flip {@see WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION}
 *     to `yes` — the detector refuses to run until this flips.
 *  2. Fire `do_action( 'woocommerce_email_template_sync_backfill_complete' )`
 *     so the first real detector sweep can run immediately, closing the
 *     ordering gap with `woocommerce_updated` (which fires before async
 *     db-update callbacks finish).
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 10.8.0
 */
class WCEmailTemplateSyncBackfill {
	/**
	 * Case identifiers for the three-case backfill algorithm.
	 *
	 * Exposed as public so consumers (currently: tests asserting classifier
	 * behaviour) can refer to the cases by name rather than by their raw
	 * single-letter values. The set is stable — any change here implies a
	 * change to the backfill's semantic contract.
	 */
	public const CASE_A = 'A';
	public const CASE_B = 'B';
	public const CASE_C = 'C';

	/**
	 * Action fired after the backfill finalizes. The divergence detector hooks
	 * onto this to run its first real sweep now that every post is stamped.
	 *
	 * @var string
	 */
	public const BACKFILL_COMPLETE_ACTION = 'woocommerce_email_template_sync_backfill_complete';

	/**
	 * Re-entrancy flag set while `wp_update_post()` writes Case B content.
	 *
	 * Future `save_post` listeners (e.g. the RSM-139 auto-apply hook) should
	 * consult {@see self::is_backfilling()} before interpreting a write as a
	 * merchant edit.
	 *
	 * @var bool
	 */
	private static $is_backfilling = false;

	/**
	 * Logger instance. Lazily instantiated on first use; overridable for tests.
	 *
	 * @var Email_Editor_Logger_Interface|null
	 */
	private static $logger = null;

	/**
	 * Action Scheduler entry point for the RSM-149 migration.
	 *
	 * Always returns `false` (one-shot). The return type is declared `bool` to
	 * match the contract {@see \WC_Install::run_update_callback_end()} expects;
	 * `(bool) false` tells the queue manager this callback is complete and
	 * should not be re-scheduled.
	 *
	 * The `woocommerce_db_version` fence around `$db_updates` provides the
	 * once-per-site guarantee, so there is no internal idempotency gate here;
	 * retry-safety comes from the `NOT EXISTS` filter on the stored source
	 * hash in {@see self::fetch_eligible_posts()}.
	 *
	 * @return bool Always false.
	 *
	 * @since 10.8.0
	 */
	public static function run(): bool {
		$eligible = self::fetch_eligible_posts();
		if ( empty( $eligible ) ) {
			self::finalize();
			return false;
		}

		$registry = WCEmailTemplateSyncRegistry::get_sync_enabled_emails();
		if ( empty( $registry ) ) {
			self::finalize();
			return false;
		}

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$emails_by_id  = $posts_manager->get_emails_by_id();

		foreach ( $eligible as $row ) {
			try {
				$post_id = (int) $row->ID;

				$email_id = (string) $posts_manager->get_email_type_from_post_id( $post_id );
				if ( '' === $email_id || ! isset( $registry[ $email_id ] ) ) {
					continue;
				}

				$email = $emails_by_id[ $email_id ] ?? null;
				if ( ! $email instanceof \WC_Email ) {
					continue;
				}

				$canonical_post_content = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );
				$current_core_hash      = sha1( $canonical_post_content );

				$case_id = self::classify( $row, $current_core_hash );

				self::apply_case_to_post( $post_id, $case_id, $canonical_post_content, $current_core_hash, $email );
			} catch ( \Throwable $e ) {
				self::get_logger()->error(
					sprintf(
						'Email template sync backfill failed for post %d: %s',
						(int) $row->ID,
						$e->getMessage()
					),
					array(
						'post_id' => (int) $row->ID,
						'context' => 'email_template_sync_backfill',
					)
				);
				continue;
			}//end try
		}//end foreach

		self::finalize();
		return false;
	}

	/**
	 * Whether the backfill is currently rewriting post content.
	 *
	 * Future save_post listeners that differentiate merchant edits from
	 * system-initiated writes should consult this flag.
	 *
	 * @return bool
	 *
	 * @since 10.8.0
	 */
	public static function is_backfilling(): bool {
		return self::$is_backfilling;
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
	 * Classify a post into one of the three backfill cases.
	 *
	 * Pure function: given identical inputs, always returns the same case.
	 *
	 * @param \stdClass $row               Row with `post_content`, `post_date`,
	 *                                     `post_modified`, `post_date_gmt`,
	 *                                     `post_modified_gmt`.
	 * @param string    $current_core_hash `sha1()` of the canonical core render for the post's email.
	 * @return string One of self::CASE_A, self::CASE_B, self::CASE_C.
	 */
	private static function classify( \stdClass $row, string $current_core_hash ): string {
		$current_post_hash = sha1( (string) ( $row->post_content ?? '' ) );

		if ( $current_post_hash === $current_core_hash ) {
			return self::CASE_A;
		}

		return self::was_never_edited( $row ) ? self::CASE_B : self::CASE_C;
	}

	/**
	 * Decide whether a row represents a post that has never been edited since
	 * creation, using the timestamp pair available.
	 *
	 * Returns true when *either* the GMT pair or the local pair compare equal.
	 * The OR makes the classifier resilient to legacy insert paths that left
	 * one pair blank or sentinel-valued, which is the common case we've
	 * observed in the wild.
	 *
	 * Known limitation: when *both* pairs independently compare equal for
	 * reasons unrelated to edit state (e.g. both `_gmt` columns are the
	 * `'0000-00-00 00:00:00'` sentinel *and* the local pair happens to match),
	 * the function can report "never edited" for a post that has in fact been
	 * edited, which would cause Case B to rewrite the merchant's content. We
	 * accept that trade-off because the population of rows where every
	 * timestamp pair is simultaneously corrupt is effectively empty in
	 * practice; the simpler predicate is worth the theoretical exposure.
	 *
	 * @param \stdClass $row Row with `post_date`, `post_modified`, `post_date_gmt`, `post_modified_gmt`.
	 * @return bool True if at least one timestamp pair matches.
	 */
	private static function was_never_edited( \stdClass $row ): bool {
		$post_date_gmt     = (string) ( $row->post_date_gmt ?? '' );
		$post_modified_gmt = (string) ( $row->post_modified_gmt ?? '' );

		$post_date     = (string) ( $row->post_date ?? '' );
		$post_modified = (string) ( $row->post_modified ?? '' );

		return $post_date_gmt === $post_modified_gmt || $post_date === $post_modified;
	}

	/**
	 * Apply the chosen case to the post: rewrite content for Case B, then stamp
	 * the four sync meta keys plus the initial status.
	 *
	 * All case-specific branching is confined to this method; callers only
	 * need to pass the pre-computed canonical content and core hash.
	 *
	 * @param int       $post_id                The target post ID.
	 * @param string    $case_id                One of self::CASE_A/B/C.
	 * @param string    $canonical_post_content The canonical core render (used by Case B).
	 * @param string    $current_core_hash      `sha1( $canonical_post_content )`.
	 * @param \WC_Email $email                  The registered email instance (used to resolve template path for the version stamp).
	 */
	private static function apply_case_to_post( int $post_id, string $case_id, string $canonical_post_content, string $current_core_hash, \WC_Email $email ): void {
		$version          = self::resolve_version_for_email( $email );
		$status_for_stamp = self::status_for_case( $case_id );

		if ( self::CASE_B === $case_id ) {
			self::$is_backfilling = true;
			try {
				$updated = wp_update_post(
					array(
						'ID'           => $post_id,
						'post_content' => $canonical_post_content,
					),
					true
				);
			} finally {
				self::$is_backfilling = false;
			}

			// With `$wp_error = true`, every `wp_update_post()` / `wp_insert_post()`
			// failure path returns `WP_Error` (the `0` return is reserved for the
			// `$wp_error = false` path). The outer `\Throwable` catch in `run()`
			// can't see a returned `WP_Error`, so we handle it here. This
			// migration is one-shot (the `woocommerce_db_version` fence flips on
			// completion), so an unstamped post would be orphaned — the detector
			// skips posts without a source hash with a recurring warning. Instead,
			// fall back to Case C semantics: stamp with the canonical hash but
			// flag the post as `core_updated_customized` so it surfaces for
			// merchant review.
			if ( is_wp_error( $updated ) ) {
				$status_for_stamp = WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED;
				self::get_logger()->warning(
					sprintf(
						'Email template sync backfill: Case B content rewrite failed for post %d (%s); stamping as core_updated_customized so the post surfaces for merchant review.',
						$post_id,
						$updated->get_error_message()
					),
					array(
						'post_id' => $post_id,
						'context' => 'email_template_sync_backfill',
					)
				);
			}
		}//end if

		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $version );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, $current_core_hash );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, $status_for_stamp );
	}

	/**
	 * Map a case to the initial status meta value.
	 *
	 * Cases A and B produce `in_sync` (both result in post content matching
	 * the canonical core render, so the stamp and the content agree). Case C
	 * deliberately seeds `core_updated_customized` — see class docblock.
	 *
	 * @param string $case_id One of self::CASE_A/B/C.
	 * @return string One of the WCEmailTemplateDivergenceDetector::STATUS_* constants.
	 */
	private static function status_for_case( string $case_id ): string {
		if ( self::CASE_C === $case_id ) {
			return WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED;
		}

		return WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC;
	}

	/**
	 * Resolve the version string for an email by parsing the `@version` tag
	 * from its block template file. Falls back to the registry's cached
	 * version if the direct parse returns empty.
	 *
	 * @param \WC_Email $email The registered email instance.
	 * @return string The parsed version, or an empty string if none can be resolved.
	 */
	private static function resolve_version_for_email( \WC_Email $email ): string {
		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( (string) $email->id );
		if ( is_array( $sync_config ) && isset( $sync_config['template_path'] ) ) {
			$parsed = WCEmailTemplateSyncRegistry::parse_version_header( (string) $sync_config['template_path'] );
			if ( '' !== $parsed ) {
				return $parsed;
			}

			return (string) ( $sync_config['version'] ?? '' );
		}

		return '';
	}

	/**
	 * Fetch every `woo_email` post that has not yet been stamped with a
	 * source hash.
	 *
	 * The `NOT EXISTS` clause is what makes the callback retry-safe: posts
	 * stamped by RSM-137 (new installs) or by a previous invocation of this
	 * migration are filtered out, so an Action Scheduler retry converges on
	 * exactly the posts that still need work.
	 *
	 * @return \stdClass[] Rows with `ID`, `post_content`, `post_date_gmt`, `post_modified_gmt`.
	 */
	private static function fetch_eligible_posts(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_content, post_date, post_modified, post_date_gmt, post_modified_gmt
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status <> 'trash'
					AND NOT EXISTS (
						SELECT 1 FROM {$wpdb->postmeta} pm
						WHERE pm.post_id = {$wpdb->posts}.ID
							AND pm.meta_key = %s
					)
				ORDER BY ID ASC",
				Integration::EMAIL_POST_TYPE,
				WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Flip the backfill-complete option and fire the completion action.
	 *
	 * Order matters: the option is updated first so any listener that
	 * inspects it inside the action sees the final state.
	 */
	private static function finalize(): void {
		update_option( WCEmailTemplateDivergenceDetector::BACKFILL_COMPLETE_OPTION, 'yes' );

		/**
		 * Fires once, immediately after the RSM-149 sync-meta backfill
		 * finalizes for this site.
		 *
		 * Hooked by {@see WCEmailTemplateDivergenceDetector::run_sweep()} so the
		 * first real divergence sweep runs with a fully-stamped post set.
		 *
		 * @since 10.8.0
		 */
		do_action( self::BACKFILL_COMPLETE_ACTION );
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
}
