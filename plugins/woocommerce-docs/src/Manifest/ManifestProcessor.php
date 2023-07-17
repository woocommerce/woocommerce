<?php

namespace WooCommerceDocs\Manifest;

use WooCommerceDocs\Blocks\BlockConverter;
use WooCommerceDocs\Data\DocsStore;

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
	 * @param int    $logger_action_id The logger action ID.
	 */
	public static function process_manifest( $manifest, $logger_action_id ) {
		self::process_categories( $manifest['categories'], $logger_action_id );
	}

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
	 * Generate post args
	 *
	 * @param mixed $post The post to generate args for.
	 * @param mixed $post_content The post content.
	 * @return array
	 */
	private static function generate_post_args( $post, $post_content ) {
		$possible_attributes = array(
			'post_title',
			'post_author',
			'post_date',
			'comment_status',
			'post_status',
		);

		$args = array();

		foreach ( $possible_attributes as $attribute ) {
			if ( isset( $post[ $attribute ] ) ) {
				$args[ $attribute ] = $post[ $attribute ];
			}
		}

		$args['post_content'] = $post_content;

		if ( ! isset( $args['post_status'] ) ) {
			$args['post_status'] = 'publish';
		}

		return $args;
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
			$term = term_exists( $category['category_title'], 'category' );

			$category_args = array( 'parent' => $parent_id );

			if ( isset( $category['category_slug'] ) ) {
				$category_args['slug'] = $category['category_slug'];
			}

			// If the category doesn't exist, create it.
			if ( 0 === $term || null === $term ) {
				$term = wp_insert_term(
					$category['category_title'],
					'category',
					$category_args
				);
			} else {
				// If the category exists, update it.
				$term = wp_update_term(
					$term['term_id'],
					'category',
					$category_args
				);
			}

			// Now, process the posts for this category.
			foreach ( $category['posts'] as $post ) {
				$existing_post = DocsStore::get_post( $post['id'] );
				$response      = wp_remote_get( $post['url'] );
				$content       = wp_remote_retrieve_body( $response );

				if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
					$error_code = wp_remote_retrieve_response_code( $response );
					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Could not retrieve ' . $post['url'] . '. status: ' . $error_code );
					continue;
				}

				$content = wp_remote_retrieve_body( $response );

				// Strip frontmatter.
				$content = preg_replace( '/^---[\s\S]*?---/', '', $content );

				// Parse markdown.
				$blocks = self::get_converter()->convert( $content );

				// If the post doesn't exist, create it.
				if ( ! $existing_post ) {
					$post_id = DocsStore::insert_docs_post(
						self::generate_post_args( $post, $blocks ),
						$post['id']
					);

					if ( isset( $post['edit_url'] ) ) {
						DocsStore::add_edit_url_to_docs_post( $post_id, $post['edit_url'] );
					}

					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Created post with id: ' . $post_id );

				} else {
					$post_update = self::generate_post_args( $post, $blocks );
					$post_update = array_merge( $post_update, array( 'ID' => $existing_post->ID ) );

					// if the post exists, update it .
					$post_id = \WoocommerceDocs\Data\DocsStore::update_docs_post(
						$post_update,
						$post['id']
					);

					if ( isset( $post['edit_url'] ) ) {
						DocsStore::add_edit_url_to_docs_post( $post_id, $post['edit_url'] );
					}

					\ActionScheduler_Logger::instance()->log( $logger_action_id, 'Updated post with id: ' . $post_id );
				}

				wp_set_post_categories( $post_id, array( $term['term_id'] ) );
			}

			// Process any sub-categories.
			if ( ! empty( $category['categories'] ) ) {
				self::process_categories( $category['categories'], $logger_action_id, $term['term_id'] );
			}
		}
	}
}

