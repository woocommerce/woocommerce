<?php
/**
 * Product Category Formatter Class
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\AI\PromptFormatter;

use Exception;
use InvalidArgumentException;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Product Category Formatter class.
 */
class Product_Category_Formatter implements Prompt_Formatter_Interface {
	private const CATEGORY_TAXONOMY         = 'product_cat';
	private const PARENT_CATEGORY_SEPARATOR = ' > ';
	private const UNCATEGORIZED_SLUG        = 'uncategorized';

	/**
	 * Get the category names from the category ids from WooCommerce and recursively get all the parent categories and prepend them to the category names separated by >.
	 *
	 * @param array $data The category ids.
	 *
	 * @return string A string containing the formatted categories. E.g., "Books > Fiction, Books > Novels > Fiction"
	 *
	 * @throws InvalidArgumentException If the input data is not an array.
	 */
	public function format( $data ): string {
		if ( ! $this->validate_data( $data ) ) {
			throw new InvalidArgumentException( 'Invalid input data. Provide an array of category ids.' );
		}

		$categories = array();
		foreach ( $data as $category_id ) {
			$category = get_term( $category_id, self::CATEGORY_TAXONOMY );

			// If the category is not found, or it is the uncategorized category, skip it.
			if ( ! $category instanceof WP_Term || self::UNCATEGORIZED_SLUG === $category->slug ) {
				continue;
			}

			$categories[] = $this->format_category_name( $category );
		}

		return implode( ', ', $categories );
	}

	/**
	 * Validates the data to make sure it can be formatted.
	 *
	 * @param mixed $data The data to format.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( $data ): bool {
		return ! empty( $data ) && is_array( $data );
	}

	/**
	 * Get formatted category name with parent categories prepended separated by >.
	 *
	 * @param WP_Term $category The category as a WP_Term object.
	 *
	 * @return string The formatted category name.
	 */
	private function format_category_name( WP_Term $category ): string {
		$parent_categories   = $this->get_parent_categories( $category->term_id );
		$parent_categories[] = $category->name;

		return implode( self::PARENT_CATEGORY_SEPARATOR, $parent_categories );
	}

	/**
	 * Get parent categories for the given category id.
	 *
	 * @param int $category_id The category id.
	 *
	 * @return array An array of names of the parent categories in the order of the hierarchy.
	 */
	private function get_parent_categories( int $category_id ): array {
		$parent_category_ids = get_ancestors( $category_id, self::CATEGORY_TAXONOMY );

		if ( empty( $parent_category_ids ) ) {
			return array();
		}

		$parent_category_ids = array_reverse( $parent_category_ids );

		return array_map(
			function ( $parent_category_id ) {
				$parent_category = get_term( $parent_category_id, self::CATEGORY_TAXONOMY );

				return $parent_category->name;
			},
			$parent_category_ids
		);
	}

}
