<?php
/**
 * LookupDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

defined( 'ABSPATH' ) || exit;

/**
 * Data store class for the product attributes lookup table.
 */
class LookupDataStore {

	/**
	 * Insert or update the lookup data for a given product or variation.
	 * If a variable product is passed the information is updated for all of its variations.
	 *
	 * @param int|WC_Product $product Product object or id.
	 * @throws \Exception A variation object is passed.
	 */
	public function update_data_for_product( $product ) {
		// TODO: For now data is always deleted and fully regenerated, existing data should be updated instead.

		if ( ! is_a( $product, \WC_Product::class ) ) {
			$product = wc_get_product( $product );
		}

		if ( $this->is_variation( $product ) ) {
			throw new \Exception( "LookupDataStore::update_data_for_product can't be called for variations." );
		}

		$this->delete_lookup_table_entries_for( $product->get_id() );

		if ( $this->is_variable_product( $product ) ) {
			$this->create_lookup_table_entries_for_variable_product( $product );
		} else {
			$this->create_lookup_table_entries_for_simple_product( $product );
		}
	}

	/**
	 * Delete all the lookup table entries for a given product
	 * (entries are identified by the "parent_or_product_id" field)
	 *
	 * @param int $product_id Simple product id, or main/parent product id for variable products.
	 */
	private function delete_lookup_table_entries_for( int $product_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM wp_wc_product_attributes_lookup WHERE product_or_parent_id = %d',
				$product_id
			)
		);
	}

	/**
	 * Create lookup table entries for a simple (non variable) product.
	 * Assumes that no entries exist yet.
	 *
	 * @param \WC_Product $product The product to create the entries for.
	 */
	private function create_lookup_table_entries_for_simple_product( \WC_Product $product ) {
		$product_attributes_data = $this->get_attribute_taxonomies( $product );
		$has_stock               = $product->is_in_stock();
		$product_id              = $product->get_id();
		foreach ( $product_attributes_data as $taxonomy => $data ) {
			$term_ids = $data['term_ids'];
			foreach ( $term_ids as $term_id ) {
				$this->insert_lookup_table_data( $product_id, $product_id, $taxonomy, $term_id, false, $has_stock );
			}
		}
	}

	/**
	 * Create lookup table entries for a variable product.
	 * Assumes that no entries exist yet.
	 *
	 * @param \WC_Product_Variable $product The product to create the entries for.
	 */
	private function create_lookup_table_entries_for_variable_product( \WC_Product_Variable $product ) {
		// TODO: Implement.
	}

	/**
	 * Check if a given product is a variable product.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if it's a variable product, false otherwise.
	 */
	private function is_variable_product( \WC_Product $product ) {
		return is_a( $product, \WC_Product_Variable::class );
	}

	/**
	 * Check if a given product is a variation.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if it's a variation, false otherwise.
	 */
	private function is_variation( \WC_Product $product ) {
		return is_a( $product, \WC_Product_Variation::class );
	}

	/**
	 * Return the list of taxonomies used for variations on a product together with
	 * the associated term ids, with the following format:
	 *
	 * [
	 *   'taxonomy_name' =>
	 *   [
	 *     'term_ids' => [id, id, ...],
	 *     'used_for_variations' => true|false
	 *   ], ...
	 * ]
	 *
	 * @param \WC_Product $product The product to get the attribute taxonomies for.
	 * @return array Information about the attribute taxonomies of the product.
	 */
	private function get_attribute_taxonomies( \WC_Product $product ) {
		$product_attributes = $product->get_attributes();
		$result             = array();
		foreach ( $product_attributes as $taxonomy_name => $attribute_data ) {
			$result[ $taxonomy_name ] = array(
				'term_ids'            => $attribute_data->get_options(),
				'used_for_variations' => $attribute_data->get_variation(),
			);
		}

		return $result;
	}

	/**
	 * Insert one entry in the lookup table.
	 *
	 * @param int    $product_id The product id.
	 * @param int    $product_or_parent_id The product id for non-variable products, the main/parent product id for variations.
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $term_id Term id.
	 * @param bool   $is_variation_attribute True if the taxonomy corresponds to an attribute used to define variations.
	 * @param bool   $has_stock True if the product is in stock.
	 */
	private function insert_lookup_table_data( int $product_id, int $product_or_parent_id, string $taxonomy, int $term_id, bool $is_variation_attribute, bool $has_stock ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO wp_wc_product_attributes_lookup (
					  product_id,
					  product_or_parent_id,
					  taxonomy,
					  term_id,
					  is_variation_attribute,
					  in_stock)
					VALUES
					  ( %d, %d, %s, %d, %d, %d )',
				$product_id,
				$product_or_parent_id,
				$taxonomy,
				$term_id,
				$is_variation_attribute ? 1 : 0,
				$has_stock ? 1 : 0
			)
		);
	}
}
