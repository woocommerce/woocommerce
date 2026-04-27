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
	 * @var string
	 */
	public const AUTO_APPLY_AS_HOOK = 'woocommerce_email_template_auto_apply_uncustomized';

	/**
	 * Action Scheduler group for the batched auto-apply runner.
	 *
	 * @var string
	 */
	public const AUTO_APPLY_AS_GROUP = 'woocommerce-email-editor';

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
	 * Atomicity: the post update plus the four meta writes run inside a single
	 * `START TRANSACTION` block, so a partial failure rolls back cleanly.
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

			$current_post_hash = sha1( (string) $post->post_content );
			if ( $current_post_hash !== $stored_source_hash ) {
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

		global $wpdb;

		$canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		self::$is_auto_applying = true;
		$wpdb->query( 'START TRANSACTION' );

		try {
			$updated = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $canonical,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				$wpdb->query( 'ROLLBACK' );
				return $updated;
			}

			$source_hash = null;
			$synced_at   = null;
			$status      = null;
			$version     = null;

			if ( null !== $sync_config ) {
				$source_hash = sha1( $canonical );
				$synced_at   = gmdate( 'Y-m-d H:i:s' );
				$status      = WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC;
				$version     = (string) $sync_config['version'];

				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $version );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, $source_hash );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, $synced_at );
				update_post_meta( $post_id, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, $status );
			}

			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		} finally {
			self::$is_auto_applying = false;
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
