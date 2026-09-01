<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

/**
 * Refreshes never-edited editing scratchpads (unpublished `woo_email` posts)
 * from the current file template, so the editor always opens on the content
 * customers would receive. Edited scratchpads are never touched.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 * @since 11.1.0
 *
 * @internal
 */
class WCEmailScratchpadRefresher {

	/**
	 * Refresh the scratchpad from the current file template when it was never
	 * edited by the user.
	 *
	 * @param \WP_Post  $scratchpad The unpublished scratchpad post.
	 * @param \WC_Email $email      The email instance.
	 */
	public function maybe_refresh( \WP_Post $scratchpad, \WC_Email $email ): void {
		if ( ! $this->was_never_edited( $scratchpad ) ) {
			return;
		}

		$this->refresh_content( $scratchpad, $email );
	}

	/**
	 * Check whether an unpublished email post was never edited by the user.
	 *
	 * Prefers the source hash stamped at creation and refresh (content
	 * untouched when it still matches); the timestamp fallback only applies to
	 * posts without a valid hash, e.g. stray unpublished posts created outside
	 * the lazy-creation flow.
	 *
	 * @param \WP_Post $post The post to check.
	 * @return bool True when the post content was never edited.
	 */
	private function was_never_edited( \WP_Post $post ): bool {
		$stored_hash = get_post_meta( $post->ID, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, true );
		if ( is_string( $stored_hash ) && 1 === preg_match( '/^[0-9a-f]{40}$/', $stored_hash ) ) {
			return sha1( (string) $post->post_content ) === $stored_hash;
		}

		return $post->post_date_gmt === $post->post_modified_gmt;
	}

	/**
	 * Refresh the scratchpad's content and title to the current file template.
	 *
	 * The title is system-owned, so it moves with the content. Keeps the
	 * sync meta baseline in step with the new content so a later
	 * `was_never_edited()` check still recognizes the post as untouched.
	 *
	 * @param \WP_Post  $scratchpad The unpublished scratchpad post.
	 * @param \WC_Email $email      The email instance.
	 */
	private function refresh_content( \WP_Post $scratchpad, \WC_Email $email ): void {
		$post_data       = WCTransactionalEmailPostsGenerator::build_filtered_post_data( (string) $email->id, $email );
		$canonical       = (string) ( $post_data['post_content'] ?? '' );
		$canonical_title = (string) ( $post_data['post_title'] ?? '' );

		if ( '' === $canonical || ( $canonical === $scratchpad->post_content && $canonical_title === $scratchpad->post_title ) ) {
			return;
		}

		$updated = wp_update_post(
			array(
				'ID'            => $scratchpad->ID,
				'post_content'  => $canonical,
				'post_title'    => $canonical_title,
				// The explicit empty value makes core skip template handling
				// entirely, leaving the stored `_wp_page_template` meta as is.
				// With the key omitted, wp_update_post() would re-inject the
				// stored template (WP_Post::to_array()) and fail with "Invalid
				// page template." whenever the email template is not
				// registered — it only is while the editor package is
				// bootstrapped.
				'page_template' => '',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return;
		}

		$saved_post = get_post( $scratchpad->ID );
		$saved_body = $saved_post instanceof \WP_Post ? (string) $saved_post->post_content : $canonical;

		// Restamped for every email, not only sync-registry ones: the update
		// above bumped `post_modified`, so without a matching hash the
		// timestamp fallback in `was_never_edited()` would treat this
		// scratchpad as edited forever and this refresh would never run again.
		update_post_meta( $scratchpad->ID, WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, sha1( $saved_body ) );

		$sync_config = WCEmailTemplateSyncRegistry::get_email_sync_config( (string) $email->id );
		if ( null !== $sync_config ) {
			update_post_meta( $scratchpad->ID, WCEmailTemplateDivergenceDetector::VERSION_META_KEY, (string) $sync_config['version'] );
			update_post_meta( $scratchpad->ID, WCEmailTemplateDivergenceDetector::LAST_SYNCED_AT_META_KEY, gmdate( 'Y-m-d H:i:s' ) );
			update_post_meta( $scratchpad->ID, WCEmailTemplateDivergenceDetector::LAST_CORE_RENDER_META_KEY, $canonical );
		}

		$scratchpad->post_content = $saved_body;
		$scratchpad->post_title   = $canonical_title;
	}
}
