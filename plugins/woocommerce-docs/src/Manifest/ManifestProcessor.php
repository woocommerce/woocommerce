<?php

namespace WooCommerceDocs\Manifest;

/**
 * Class ManifestProcessor
 *
 * @package WooCommerceDocs\Manifest
 */
class ManifestProcessor {
	/**
	 * Process manifest object into WordPress pages
	 *
	 * @param Object $manifest The manifest to process.
	 */
	public static function process_manifest( $manifest, $logger_action_id ) {
		self::process_categories( $manifest['categories'], $logger_action_id );
	}

	/**
	 * Get the parsedown parser
	 */
	// private static function get_parser() {
	// static $parser = null;
	// if ( null === $parser ) {
	// $parser = new \Parsedown();
	// }
	// return $parser;
	// }

	/**
	 * Process categories
	 *
	 * @param array $categories The categories to process.
	 * @param int   $parent_id The parent ID.
	 */
	private static function process_categories( $categories, $logger_action_id, $parent_id = 0 ) {
		foreach ( $categories as $category ) {
			$term = term_exists( $category['title'], 'category' );

			// If the category doesn't exist, create it.
			if ( 0 === $term || null === $term ) {
				$term = wp_insert_term(
					$category['title'],
					'category',
					array(
						'parent' => $parent_id,
					)
				);
			} else {
				// If the category exists, update it.
				$term = wp_update_term(
					$term['term_id'],
					'category',
					array(
						'parent' => $parent_id,
					)
				);
			}

			// Now, process the pages for this category.
			foreach ( $category['pages'] as $page ) {
				\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Processing page: ' . $page['title'] . ' with id: ' . $page['id'] );

				$existing_post = \WooCommerceDocs\Data\DocsStore::get_post( $page['id'] );
				$response      = wp_remote_get( $page['url'] );
				$content       = wp_remote_retrieve_body( $response );

				// if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				// $error_code = wp_remote_retrieve_response_code( $response );
				// \ActionScheduler_Logger::instance()->log( $logger_action_id, 'Could not retrieve ' . $page['url'] . '. status: ' . $error_code );
				// continue;
				// } else {
				// $markdown_content = wp_remote_retrieve_body( $response );
				// \ActionScheduler_Logger::instance()->log( $logger_action_id, 'Retrieved page: ' . $markdown_content );
				// }

				$content = '<p>Hello World</p>';

				// If the page doesn't exist, create it.
				if ( ! $existing_post ) {
					$post_id = \WooCommerceDocs\Data\DocsStore::insert_docs_post(
						array(
							'post_title'   => $page['title'],
							'post_content' => $content,
							'post_status'  => 'publish',
						),
						$page['id']
					);

					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Created page with id: ' . $post_id );

				} else {
					// if the page exists, update it .
					$post_id = \WoocommerceDocs\Data\DocsStore::update_docs_post(
						array(
							'ID'           => $existing_post->ID,
							'post_title'   => $page['title'],
							'post_content' => $content,
						),
						$page['id']
					);

					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Updated page with id: ' . $post_id );
				}
			}

			// Process any sub-categories.
			if ( ! empty( $category['categories'] ) ) {
				self::process_categories( $category['categories'], $term['term_id'] );
			}
		}
	}
}

