<?php
/**
 * Product Category Formatter Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptUtils;

defined( 'ABSPATH' ) || exit;

class Product_Category_Formatter {
	/**
	 * Get the category names from the category ids from WooCommerce and recursively get all the parent categories and prepend them to the category names separated by >.
	 *
	 * @param array $category_ids The category ids.
	 *
	 * @return string[] An array of category names.
	 */
	public function get_category_names( array $category_ids ): array {
		if ( empty( $categories ) ) {
			return array();
		}

		return array_map(
			function ( $category_id ) {
				$category            = get_term( $category_id, 'product_cat' );
				$parent_categories   = get_ancestors( $category_id, 'product_cat' );
				$parent_categories   = array_reverse( $parent_categories );
				$parent_categories   = array_map(
					function ( $parent_category_id ) {
						$parent_category = get_term( $parent_category_id, 'product_cat' );

						return $parent_category->name;
					},
					$parent_categories
				);
				$parent_categories[] = $category->name;

				return implode( ' > ', $parent_categories );
			},
			$category_ids
		);
	}
}
