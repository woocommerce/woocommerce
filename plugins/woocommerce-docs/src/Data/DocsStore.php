<?php
/**
 * DocsStore class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Data;

/**
 * DocsStore class file
 *
 * @package  WooCommerceDocs
 */
class DocsStore {

	/**
	 * Get a list of docs posts
	 */
	public static function get_posts() {
		$args = array(
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tag'            => 'woocommerce_docs',
		);

		return get_posts( $args );
	}

	/**
	 * Get a docs post by doc ID.
	 *
	 * @param int $doc_id The doc ID to get.
	 */
	public static function get_post( $doc_id ) {
		$args = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'   => 'docs_id',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value' => $doc_id,
			'tag'        => 'woocommerce_docs',
		);

		$existing_page = get_posts( $args );

		return $existing_page[0] ?? null;
	}

	/**
	 * Add a docs post
	 *
	 * @param array $post The post to add.
	 * @param int   $doc_id The doc ID to assign to the post.
	 */
	public static function insert_docs_post( $post, $doc_id ) {
		$post_id = wp_insert_post( $post );

		update_post_meta( $post_id, 'docs_id', $doc_id );
		wp_set_post_tags( $post_id, 'woocommerce_docs', true );

		return $post_id;
	}

	/**
	 * Update a docs post
	 *
	 * @param array $post The post to update.
	 * @param int   $doc_id The doc ID of the post to update.
	 */
	public static function update_docs_post( $post, $doc_id ) {
		$args = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'   => 'docs_id',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value' => $doc_id,
			'tag'        => 'woocommerce_docs',
		);

		$existing_page = get_posts( $args );

		if ( ! empty( $existing_page ) ) {
			$post['ID'] = $existing_page[0]->ID;
			return wp_update_post( $post );
		}
	}

	/**
	 * Delete a docs post
	 *
	 * @param int $post_id The post ID to delete.
	 */
	public static function delete_docs_post( $post_id ) {
		return wp_delete_post( $post_id );
	}

	/**
	 * Initialize the docs store.
	 */
	public static function setup() {
		self::create_docs_tag();
	}

	/**
	 * Create the docs tag
	 */
	private static function create_docs_tag() {
		$tag      = 'woocommerce_docs';
		$taxonomy = 'post_tag';

		$existing_tag = term_exists( $tag, $taxonomy );

		if ( ! $existing_tag ) {
			$tag_args = array(
				'slug' => sanitize_title( $tag ),
			);
			wp_insert_term( $tag, $taxonomy, $tag_args );
		}
	}
}





