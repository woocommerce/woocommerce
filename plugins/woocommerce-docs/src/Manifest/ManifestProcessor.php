<?php

namespace WooCommerceDocs\Manifest;

use WooCommerceDocs\Data\DocsStore;

/**
 * Class ManifestProcessor
 *
 * @package WooCommerceDocs\Manifest
 */
class ManifestProcessor {
	/**
	 * Process manifest object into WordPress posts and categories.
	 *
	 * @param Object $manifest The manifest to process.
	 * @param int    $logger_action_id The logger action ID.
	 * @param Object $previous_manifest The previous manifest.
	 */
	public static function process_manifest( $manifest, $logger_action_id, $previous_manifest = null ) {
		if ( $previous_manifest ) {
			PostRemover::remove_deleted_posts( $manifest, $previous_manifest, $logger_action_id );
		}
		self::process_categories( $manifest['categories'], $logger_action_id );
	}

	/**
	 * Process categories
	 *
	 * @param array $categories The categories to process.
	 * @param int   $logger_action_id The logger action ID.
	 * @param int   $parent_id The parent ID.
	 */
	private static function process_categories( $categories, $logger_action_id, $parent_id = 0 ) {
		foreach ( $categories as $category ) {
			$term = CategoryCreator::create_or_update_category_from_manifest_entry( $category, $parent_id );

			// Now, process the posts for this category.
			foreach ( $category['posts'] as $post ) {
				$response = wp_remote_get( $post['url'] );
				$content  = wp_remote_retrieve_body( $response );

				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
					$error_code = wp_remote_retrieve_response_code( $response );
					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Could not retrieve ' . $post['url'] . '. status: ' . $error_code );
					continue;
				}

				$content = wp_remote_retrieve_body( $response );

				$post_id = PostCreator::create_or_update_post_from_manifest_entry( $post, $content, $category['category_title'], $logger_action_id );

				wp_set_post_categories( $post_id, array( $term['term_id'] ) );
			}

			// Process any sub-categories.
			if ( ! empty( $category['categories'] ) ) {
				self::process_categories( $category['categories'], $logger_action_id, $term['term_id'] );
			}
		}
	}

	/**
	 * Recusively collect post IDs from a manifest.
	 *
	 * @param Object $manifest The manifest to process.
	 */
	public static function collect_doc_ids_from_manifest( $manifest ) {
		$doc_ids = array();
		foreach ( $manifest['categories'] as $category ) {
			foreach ( $category['posts'] as $post ) {
				$doc_ids[] = $post['id'];
			}
			$subcategory_ids = self::collect_doc_ids_from_manifest( $category );
			$doc_ids         = array_merge( $doc_ids, $subcategory_ids );
		}

		return $doc_ids;
	}
}

