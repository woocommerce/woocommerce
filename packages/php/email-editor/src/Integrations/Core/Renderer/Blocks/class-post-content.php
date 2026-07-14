<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

/**
 * Stateless renderer for core/post-content block.
 *
 * This renderer replaces WordPress's default render_block_core_post_content()
 * which uses a static $seen_ids array that causes issues when rendering multiple
 * emails in a single request (e.g., MailPoet batch processing).
 *
 * Unlike other block renderers, this class does NOT extend Abstract_Block_Renderer
 * because it needs to directly replace WordPress's render_callback with a method
 * that matches the exact signature expected by WordPress.
 */
class Post_Content {
	/**
	 * Stateless render callback for core/post-content block.
	 *
	 * This implementation avoids using get_the_content() which relies on
	 * global query state, and instead directly accesses post content
	 * and applies the_content filter for processing.
	 *
	 * Key differences from WordPress's implementation:
	 * - No static $seen_ids array (allows multiple renders in same request)
	 * - Uses direct post content access instead of get_the_content()
	 * - Properly backs up and restores global state
	 *
	 * IMPORTANT: This method is only set as the render_callback during email rendering.
	 * Outside of email rendering, the original callback is restored, so this method
	 * will never be called in non-email contexts.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered post content HTML.
	 */
	public function render_stateless( $attributes, $content, $block ): string {
		// This method is only called during email rendering, so we always use stateless logic.
		$post_id = $block->context['postId'] ?? null;

		if ( ! $post_id ) {
			return '';
		}

		$email_post = get_post( $post_id );
		if ( ! $email_post || empty( $email_post->post_content ) ) {
			return '';
		}

		// Backup global state.
		global $post, $wp_query;
		$backup_post  = $post;
		$backup_query = $wp_query;

		// Set up global state for block rendering.
		// This ensures that blocks which depend on global $post work correctly.
		$post = $email_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		// Create a query specifically for this post to ensure proper context.
		$wp_query = new \WP_Query( array( 'p' => $post_id ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Get raw post content and apply the_content filter.
		// The the_content filter processes blocks, shortcodes, etc.
		// We don't use get_the_content() to avoid issues with loop state.
		$post_content = $email_post->post_content;

		// Check for nextpage to display page links for paginated posts.
		if ( has_block( 'core/nextpage', $email_post ) ) {
			$post_content .= wp_link_pages( array( 'echo' => 0 ) );
		}

		// Apply the_content filter to process blocks.
		$post_content = apply_filters( 'the_content', str_replace( ']]>', ']]&gt;', $post_content ) );

		// Restore global state.
		$post     = $backup_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = $backup_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( empty( $post_content ) ) {
			return '';
		}

		return $post_content;
	}
}
