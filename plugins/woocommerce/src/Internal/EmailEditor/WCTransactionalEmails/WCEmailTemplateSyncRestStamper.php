<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use WP_Post;
use WP_REST_Request;

/**
 * Stamps sync meta on a woo_email post when a REST update matches the current-core template render.
 *
 * Hooked into `rest_after_insert_woo_email`. When the saved post_content is semantically
 * equivalent to the current core template render for that email type, we treat the save as a
 * merchant reset and refresh all sync meta keys:
 *  - `_wc_email_template_version`
 *  - `_wc_email_template_source_hash`
 *  - `_wc_email_last_synced_at`
 *  - `_wc_email_template_status` (set to `in_sync`)
 *
 * "Semantically equivalent" is determined after normalizing both the canonical server content and
 * the editor-saved content through {@see self::normalize_for_comparison()}, which strips the
 * leading/trailing whitespace and tag-adjacent spaces that the block editor's parse→serialize
 * cycle removes.
 *
 * Emails not present in the sync registry (no parseable `@version` header, or third-party
 * emails that have not opted in) are silently skipped.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 */
class WCEmailTemplateSyncRestStamper {

	/**
	 * Manages CRUD and lookup of transactional email posts.
	 *
	 * @var WCTransactionalEmailPostsManager
	 */
	private WCTransactionalEmailPostsManager $post_manager;

	/**
	 * Constructor.
	 *
	 * @param WCTransactionalEmailPostsManager $post_manager Post manager instance.
	 */
	public function __construct( WCTransactionalEmailPostsManager $post_manager ) {
		$this->post_manager = $post_manager;
	}

	/**
	 * Callback for the `rest_after_insert_woo_email` action.
	 *
	 * Stamps sync meta when the saved post_content hash matches the current-core template render.
	 *
	 * @param WP_Post         $post     The updated post object (already persisted to the DB).
	 * @param WP_REST_Request $request  The REST request.
	 * @param bool            $creating True when the post was just created; false on updates.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @since 10.8.0
	 */
	public function maybe_stamp_after_rest_update( WP_Post $post, WP_REST_Request $request, bool $creating ): void {
		// New posts are stamped at generation time by WCTransactionalEmailPostsGenerator.
		if ( $creating ) {
			return;
		}

		$email_type = $this->post_manager->get_email_type_from_post_id( $post->ID );
		if ( null === $email_type ) {
			return;
		}

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( $email_type );
		if ( null === $sync_config ) {
			return;
		}

		$email = $this->resolve_wc_email( $email_type );
		if ( null === $email ) {
			return;
		}

		$canonical = WCTransactionalEmailPostsGenerator::compute_canonical_post_content( $email );

		// Compare after normalizing both sides: the block editor's parse→serialize cycle strips
		// leading/trailing whitespace and tag-adjacent spaces, so exact string equality never holds.
		if ( self::normalize_for_comparison( $canonical ) !== self::normalize_for_comparison( $post->post_content ) ) {
			return;
		}

		// Content matches the core template — treat this as a reset and refresh all sync meta.
		// Store sha1 of the actual saved content (not the canonical) so the divergence detector
		// can later recognise the post as "uncustomized" when core updates.
		update_post_meta( $post->ID, '_wc_email_template_version', $sync_config['version'] );
		update_post_meta( $post->ID, '_wc_email_template_source_hash', sha1( $post->post_content ) );
		update_post_meta( $post->ID, '_wc_email_last_synced_at', gmdate( 'Y-m-d H:i:s' ) );
		update_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::STATUS_META_KEY, WCEmailTemplateDivergenceDetector::STATUS_IN_SYNC );
	}

	/**
	 * Normalize block content for reset-detection comparison.
	 *
	 * The block editor's parse→serialize cycle removes:
	 *   - leading/trailing whitespace from the overall content, and
	 *   - whitespace immediately after an opening tag or before a closing tag
	 *     (e.g. `<div> ##content## </div>` → `<div>##content##</div>`).
	 *
	 * Applying the same normalization to both the server-rendered canonical and the
	 * editor-saved content makes the hashes comparable even when raw PHP output and
	 * Gutenberg's serialized output differ only in insignificant whitespace.
	 *
	 * Note: the tag-adjacent regex also collapses whitespace inside text nodes that happen
	 * to sit immediately after `>` or before `<` (e.g. ` We look forward to... `).
	 * This could theoretically cause a false-positive match, but in practice both sides are
	 * normalized symmetrically, so such content would collapse identically on both sides and
	 * the false positive would correctly reflect a semantic reset.
	 *
	 * @param string $content Raw block content.
	 * @return string Normalized content.
	 */
	private static function normalize_for_comparison( string $content ): string {
		$content = trim( $content );
		// Strip whitespace immediately following a closing angle bracket (e.g. "> \n  text" → ">text").
		$content = (string) preg_replace( '/>\s+/', '>', $content );
		// Strip whitespace immediately preceding an opening angle bracket (e.g. "text \n  <" → "text<").
		$content = (string) preg_replace( '/\s+</', '<', $content );
		return $content;
	}

	/**
	 * Resolve the WC_Email instance for the given email type ID.
	 *
	 * @param string $email_type The email type ID (e.g. `customer_new_account`).
	 * @return \WC_Email|null
	 */
	private function resolve_wc_email( string $email_type ): ?\WC_Email {
		$class_name = $this->post_manager->get_email_type_class_name_from_email_id( $email_type );
		if ( null === $class_name ) {
			return null;
		}
		return WC()->mailer()->get_emails()[ $class_name ] ?? null;
	}
}
