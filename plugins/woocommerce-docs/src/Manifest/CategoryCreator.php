<?php

namespace WooCommerceDocs\Manifest;

/**
 * Class CategoryCreator
 *
 * @package WooCommerceDocs\Manifest
 */
class CategoryCreator {
	/**
	 * Create categories from manifest.
	 *
	 * @param mixed $manifest_category The manifest category.
	 * @param mixed $parent_id The parent ID.
	 * @return array The term.
	 */
	public static function create_or_update_category_from_manifest_entry( $manifest_category, $parent_id ) {
		$term          = term_exists( $manifest_category['category_title'], 'category' );
		$category_args = array( 'parent' => $parent_id );

		if ( isset( $manifest_category['category_slug'] ) ) {
			$category_args['slug'] = $manifest_category['category_slug'];
		}

		// If the category doesn't exist, create it.
		if ( 0 === $term || null === $term ) {
			$term = wp_insert_term(
				$manifest_category['category_title'],
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

		return $term;
	}
}
