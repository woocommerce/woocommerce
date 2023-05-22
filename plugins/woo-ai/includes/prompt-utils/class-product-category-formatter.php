<?php
/**
 * Product Category Formatter Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Product Category Formatter class.
 */
class Product_Category_Formatter {

	/**
	 * Get the category names from the category ids from WooCommerce and recursively get all the parent categories and prepend them to the category names separated by >.
	 *
	 * @param array $category_ids The category ids.
	 *
	 * @return string[] An array of category names.
	 */
	public function get_category_names( array $category_ids ): array {
		// Return an empty array if the input category_ids is empty.
		if ( empty( $category_ids ) ) {
			return array();
		}

		return array_map(
			function ( $category_id ) {
				return $this->format_category_name( $category_id );
			},
			$category_ids
		);
	}

	/**
	 * Get formatted category name with parent categories prepended separated by >.
	 *
	 * @param int $category_id The category id.
	 *
	 * @return string The formatted category name.
	 */
	private function format_category_name( int $category_id ): string {
		$category            = get_term( $category_id, 'product_cat' );
		$parent_categories   = $this->get_parent_categories( $category_id );
		$parent_categories[] = $category->name;

		return implode( ' > ', $parent_categories );
	}

	/**
	 * Get parent categories for the given category id.
	 *
	 * @param int $category_id The category id.
	 *
	 * @return array An array of names of the parent categories in the order of the hierarchy.
	 */
	private function get_parent_categories( int $category_id ): array {
		$parent_category_ids = get_ancestors( $category_id, 'product_cat' );
		$parent_category_ids = array_reverse( $parent_category_ids );

		return array_map(
			function ( $parent_category_id ) {
				$parent_category = get_term( $parent_category_id, 'product_cat' );

				return $parent_category->name;
			},
			$parent_category_ids
		);
	}

}
