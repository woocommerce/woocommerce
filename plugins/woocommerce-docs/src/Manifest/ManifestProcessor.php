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
	public static function process_manifest( $manifest ) {
		self::process_categories( $manifest->categories );
	}

	/**
	 * Get the parsedown parser
	 */
	private static function get_parser() {
		static $parser = null;
		if ( null === $parser ) {
			$parser = new \Parsedown();
		}
		return $parser;
	}

	/**
	 * Process categories
	 *
	 * @param array $categories The categories to process.
	 * @param int   $parent_id The parent ID.
	 */
	private static function process_categories( $categories, $parent_id = 0 ) {
		foreach ( $categories as $category ) {
			// TODO - for each category we will need to query any existing pages that arent in the manifest
			// and remove them.

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
				$existing_post = \WooCommerceDocs\Data\DocsStore::get_post( $page['id'] );

				$markdown_content = wp_remote_get( $page['url'] );

				// Strip frontmatter.
				$markdown_content = preg_replace( '/^---(.*)---/s', '', $markdown_content );

				// Parse to HTML.
				$content = self::get_parser()->text( $markdown_content );

				// If the page doesn't exist, create it.
				if ( ! $existing_post ) {
					$post_id = \WooCommerceDocs\Data\DocsStore::insert_docs_post(
						array(
							'post_title'   => $page['title'],
							'post_content' => $content,
							'post_status'  => 'publish',
							'post_type'    => 'woocommerce_doc',
						)
					);

				} else {
					// If the page exists, update it.
					\WoocommerceDocs\Data\DocsStore::update_docs_post(
						array(
							'ID'           => $existing_post->ID,
							'post_title'   => $page['title'],
							'post_content' => $content,
						)
					);
				}
			}

			// Process any sub-categories.
			if ( ! empty( $category['categories'] ) ) {
				self::process_categories( $category['categories'], $term['term_id'] );
			}
		}
	}
}

