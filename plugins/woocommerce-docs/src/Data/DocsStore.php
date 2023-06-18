<?php

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
			'post_type'      => 'docs',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		return get_posts( $args );
	}

	/**
	 * Add a docs post
	 *
	 * @param array $post The post to add.
	 */
	public static function add_docs_post( $post ) {
		$post_id = wp_insert_post( $post );
		return $post_id;
	}
}
