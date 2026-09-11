<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Logger;

/**
 * One-time cleanup of never-customized `woo_email` posts (WOOPLUG-6171).
 *
 * With file templates as the rendering source of truth until an email is
 * edited and saved, stored copies that were bulk-generated on initialization
 * and never touched by a merchant only freeze outdated content. This
 * migration hard-deletes them (and their option mapping) so the affected
 * emails fall back to the file template — picking up template updates and the
 * site's current locale. Customized posts are left untouched and are stamped
 * with the `_wc_email_type` meta that lazily created posts carry.
 *
 * Runs once per site via WooCommerce's db-updates pipeline (see
 * {@see \WC_Install::$db_updates}); the `woocommerce_db_version` fence
 * guarantees single execution and re-runs converge because deleted mappings
 * are gone. A single synchronous pass is sufficient — the post set is bounded
 * by the number of registered transactional emails.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 11.1.0
 */
class WCEmailPostsCleanup {
	/**
	 * Transient formerly used to gate bulk post generation; retired with it.
	 *
	 * @var string
	 */
	const LEGACY_GENERATION_TRANSIENT = 'wc_email_editor_initial_templates_generated';

	/**
	 * Entry point for the db-update callback.
	 *
	 * Always returns `false` (one-shot), matching the contract
	 * {@see \WC_Install::run_update_callback_end()} expects.
	 *
	 * @param Email_Editor_Logger_Interface|null $logger Logger to report to; defaults to the WooCommerce logger.
	 * @return bool Always false.
	 *
	 * @since 11.1.0
	 */
	public static function run( ?Email_Editor_Logger_Interface $logger = null ): bool {
		$logger = $logger ?? new Logger( wc_get_logger() );

		$posts_manager = WCTransactionalEmailPostsManager::get_instance();
		$emails_by_id  = $posts_manager->get_emails_by_id();

		$deleted = 0;
		$kept    = 0;

		foreach ( self::fetch_email_post_mappings() as $mapping ) {
			try {
				$option_name = (string) $mapping->option_name;
				$post_id     = (int) $mapping->option_value;
				$email_type  = self::email_type_from_option_name( $option_name );

				// The SQL LIKE match is loose (each `_` is a single-char
				// wildcard); never touch options that only resemble the
				// mapping shape — they belong to someone else.
				if ( null === $email_type ) {
					continue;
				}

				if ( $post_id <= 0 ) {
					delete_option( $option_name );
					continue;
				}

				$post = get_post( $post_id );
				if ( ! $post instanceof \WP_Post || Integration::EMAIL_POST_TYPE !== $post->post_type ) {
					// Orphaned mapping.
					delete_option( $option_name );
					continue;
				}

				// A trashed copy no longer affects rendering; treat it as "revert to
				// default". The email editor UI never trashes woo_email posts (its
				// delete action permanently deletes via the
				// `woocommerce_email_editor_trash_modal_should_permanently_delete`
				// filter), so a trashed post slipped past that guard out-of-band.
				if ( 'trash' === $post->post_status ) {
					if ( wp_delete_post( $post->ID, true ) ) {
						delete_option( $option_name );
						++$deleted;
					}
					continue;
				}

				$email = $emails_by_id[ $email_type ] ?? null;
				if ( ! $email instanceof \WC_Email ) {
					// Cannot compute the canonical content (e.g. extension deactivated);
					// keep the post — it keeps rendering from the DB as before.
					update_post_meta( $post->ID, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, $email_type );
					++$kept;
					continue;
				}

				if ( self::was_never_customized( $post, $email ) ) {
					// When the deletion fails (or a `pre_delete_post` filter
					// short-circuits it with a falsy value), keep the mapping —
					// otherwise the post would linger published while the email
					// falls back to the file template.
					if ( ! wp_delete_post( $post->ID, true ) ) {
						continue;
					}
					// `before_delete_post` also removes the mapping when the email
					// editor feature is active; delete the option defensively for
					// the case it isn't.
					delete_option( $option_name );
					++$deleted;
				} else {
					update_post_meta( $post->ID, WCTransactionalEmailPostsManager::EMAIL_TYPE_META_KEY, $email_type );
					++$kept;
				}
			} catch ( \Throwable $e ) {
				$logger->error(
					sprintf(
						'Email posts cleanup failed for mapping %s: %s',
						(string) $mapping->option_name,
						$e->getMessage()
					),
					array(
						'option_name' => (string) $mapping->option_name,
						'context'     => 'email_posts_cleanup',
					)
				);
				continue;
			}
		}

		delete_transient( self::LEGACY_GENERATION_TRANSIENT );

		$posts_manager->clear_caches();

		$logger->info(
			sprintf( 'Email posts cleanup finished: %d never-customized post(s) deleted, %d customized post(s) kept.', $deleted, $kept ),
			array( 'context' => 'email_posts_cleanup' )
		);

		return false;
	}

	/**
	 * Decide whether a stored email post was never customized by a merchant.
	 *
	 * True when any of the following independent signals holds:
	 * - the content matches the canonical core render recomputed right now;
	 * - the content still matches the sync source hash stamped at creation or
	 *   backfill time (untouched even though core moved on — deleting is
	 *   equivalent to what the auto-applier would do, just cheaper);
	 * - no valid source hash exists (email not covered by the sync registry)
	 *   and the GMT creation/modification timestamps show no edit ever happened.
	 *
	 * The stored `_wc_email_template_status` meta is deliberately not trusted
	 * on its own — it can be stale (sweeps only run after upgrades) or absent.
	 *
	 * @param \WP_Post  $post  The stored email post.
	 * @param \WC_Email $email The registered email instance.
	 * @return bool True when the post can safely fall back to the file template.
	 */
	private static function was_never_customized( \WP_Post $post, \WC_Email $email ): bool {
		$post_hash = sha1( (string) $post->post_content );

		$canonical_hash = sha1( WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email ) );
		if ( $post_hash === $canonical_hash ) {
			return true;
		}

		$stored_hash = get_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
		if ( is_string( $stored_hash ) && 1 === preg_match( '/^[0-9a-f]{40}$/', $stored_hash ) ) {
			return $post_hash === $stored_hash;
		}

		// GMT columns only: the local pair is computed with the site offset at
		// write time, so a timezone change between creation and an edit can
		// make it match coincidentally — and any extra signal here widens a
		// hard-delete decision.
		return $post->post_date_gmt === $post->post_modified_gmt;
	}

	/**
	 * Fetch all email type → post ID option mappings straight from the database.
	 *
	 * Bypasses the posts manager caches on purpose: this is a migration and
	 * must observe the persisted state.
	 *
	 * @return \stdClass[] Rows with `option_name` and `option_value`.
	 */
	private static function fetch_email_post_mappings(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC",
				WCTransactionalEmailPostsManager::WC_OPTION_NAME
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Derive the email type from a mapping option name.
	 *
	 * @param string $option_name Option name, e.g. `woocommerce_email_templates_customer_new_account_post_id`.
	 * @return string|null The email type, e.g. `customer_new_account`, or null when
	 *                     the name doesn't match the mapping shape exactly.
	 */
	private static function email_type_from_option_name( string $option_name ): ?string {
		if ( 1 !== preg_match( '/^woocommerce_email_templates_(.+)_post_id$/', $option_name, $matches ) ) {
			return null;
		}

		return $matches[1];
	}
}
