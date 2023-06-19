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
			'post_type'      => 'woocommerce_doc',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
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
			'post_type'  => 'woocommerce_doc',
			'meta_key'   => 'docs_id',
			'meta_value' => $doc_id,
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
			'post_type'  => 'woocommerce_doc',
			'meta_key'   => 'docs_id',
			'meta_value' => $doc_id,
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

	public static function hook_post_type() {
		// add_action( 'init', 'register_woocommerce_doc_post_type' );
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	}

	public static function register_post_type() {
		register_post_type(
			'woocommerce_doc',
			array(
				'labels'      => array(
					'name'          => __( 'WooCommerce Docs' ),
					'singular_name' => __( 'WooCommerce Doc' ),
				),
				'public'      => true,
				'has_archive' => true,
				'supports'    => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
				'rewrite'     => array( 'slug' => 'docs' ),
			)
		);
	}
}





