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
	 * Get the doc ID from a post ID
	 *
	 * @param int $post_id The post ID to get the doc ID from.
	 */
	public static function get_doc_id_by_post_id( $post_id ) {
		return get_post_meta( $post_id, 'docs_id', true );
	}

	/**
	 * Add an edit URL to a docs post
	 *
	 * @param int    $post_id - The post ID to add the edit URL to.
	 * @param string $edit_url  - The edit URL to add.
	 * @return void
	 */
	public static function add_edit_url_to_docs_post( $post_id, $edit_url ) {
		update_post_meta( $post_id, 'edit_url', $edit_url );
	}

	/**
	 * Get the edit URL from a docs post
	 *
	 * @param int $post_id - The post ID to get the edit URL from.
	 * @return string The edit URL.
	 */
	public static function get_edit_url_from_docs_post( $post_id ) {
		return get_post_meta( $post_id, 'edit_url', true );
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

		$existing_doc = get_posts( $args );

		if ( ! empty( $existing_doc ) ) {
			return wp_update_post( $post );
		}
	}

	/**
	 * Delete a docs post
	 *
	 * @param int $doc_id The ID to delete from a post's entry in the manifest.
	 */
	public static function delete_docs_post( $doc_id ) {
		$post = self::get_post( $doc_id );
		if ( null !== $post ) {
			return wp_delete_post( $post->ID, true );
		} else {
			return false;
		}
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





