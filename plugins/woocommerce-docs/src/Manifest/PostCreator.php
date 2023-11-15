<?php

namespace WooCommerceDocs\Manifest;

use WooCommerceDocs\Data\DocsStore;
use WooCommerceDocs\Blocks\BlockConverter;

/**
 * Class PostCreator
 *
 * Create a post from a manifest entry.
 *
 * @package WooCommerceDocs\Manifest
 */
class PostCreator {
	/**
	 * Get the Markdown converter.
	 */
	private static function get_converter() {
		static $converter = null;
		if ( null === $converter ) {
			$converter = new BlockConverter();
		}
		return $converter;
	}

	/**
	 * Create a post from a manifest entry and file contents.
	 *
	 * @param mixed  $manifest_post The manifest representation of the post.
	 * @param string $post_content The post content as a string.
	 * @param int    $logger_action_id The logger action ID.
	 * @return int The post ID.
	 */
	public static function create_or_update_post_from_manifest_entry( $manifest_post, $post_content, $logger_action_id ) {
		$existing_post = DocsStore::get_post( $manifest_post['id'] );

		$blocks = self::get_converter()->convert_markdown_to_gb_blocks( $post_content );

		// Generate post args.
		$post_args = new PostArgs( $manifest_post, $blocks );

		// If the post doesn't exist, create it.
		if ( ! $existing_post ) {
			$post_id = DocsStore::insert_docs_post(
				$post_args->get_args(),
				$manifest_post['id']
			);

			if ( isset( $manifest_post['edit_url'] ) ) {
				DocsStore::add_edit_url_to_docs_post( $post_id, $manifest_post['edit_url'] );
			}

			\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Created post with id: ' . $post_id );
		} else {
			$post_update = array_merge( $post_args->get_args(), array( 'ID' => $existing_post->ID ) );

			// if the post exists, update it .
			$post_id = \WoocommerceDocs\Data\DocsStore::update_docs_post(
				$post_update,
				$manifest_post['id']
			);

			if ( isset( $manifest_post['edit_url'] ) ) {
				DocsStore::add_edit_url_to_docs_post( $post_id, $manifest_post['edit_url'] );
			}

			\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Updated post with id: ' . $post_id );
		}

		return $post_id;
	}
}
