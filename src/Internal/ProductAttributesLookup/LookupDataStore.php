<?php
/**
 * LookupDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Data store class for the product attributes lookup table.
 */
class LookupDataStore {

	/**
	 * The lookup table name.
	 *
	 * @var string
	 */
	private $lookup_table_name;

	/**
	 * Is the feature visible?
	 *
	 * @var bool
	 */
	private $is_feature_visible;

	/**
	 * LookupDataStore constructor. Makes the feature hidden by default.
	 */
	public function __construct() {
		global $wpdb;

		$this->lookup_table_name  = $wpdb->prefix . 'wc_product_attributes_lookup';
		$this->is_feature_visible = false;
	}

	/**
	 * Checks if the feature is visible (so that dedicated entries will be added to the debug tools page).
	 *
	 * @return bool True if the feature is visible.
	 */
	public function is_feature_visible() {
		return $this->is_feature_visible;
	}

	/**
	 * Makes the feature visible, so that dedicated entries will be added to the debug tools page.
	 */
	public function show_feature() {
		$this->is_feature_visible = true;
	}

	/**
	 * Hides the feature, so that no entries will be added to the debug tools page.
	 */
	public function hide_feature() {
		$this->is_feature_visible = false;
	}

	/**
	 * Get the name of the lookup table.
	 *
	 * @return string
	 */
	public function get_lookup_table_name() {
		return $this->lookup_table_name;
	}

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
			$product = WC()->call_function( 'wc_get_product', $product );
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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $this->lookup_table_name . ' WHERE product_or_parent_id = %d',
				$product_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
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
		$product_attributes_data       = $this->get_attribute_taxonomies( $product );
		$variation_attributes_data     = array_filter(
			$product_attributes_data,
			function( $item ) {
				return $item['used_for_variations'];
			}
		);
		$non_variation_attributes_data = array_filter(
			$product_attributes_data,
			function( $item ) {
				return ! $item['used_for_variations'];
			}
		);

		$main_product_has_stock = $product->is_in_stock();
		$main_product_id        = $product->get_id();

		foreach ( $non_variation_attributes_data as $taxonomy => $data ) {
			$term_ids = $data['term_ids'];
			foreach ( $term_ids as $term_id ) {
				$this->insert_lookup_table_data( $main_product_id, $main_product_id, $taxonomy, $term_id, false, $main_product_has_stock );
			}
		}

		$term_ids_by_slug_cache = $this->get_term_ids_by_slug_cache( array_keys( $variation_attributes_data ) );
		$variations             = $this->get_variations_of( $product );

		foreach ( $variation_attributes_data as $taxonomy => $data ) {
			foreach ( $variations as $variation ) {
				$variation_id                 = $variation->get_id();
				$variation_has_stock          = $variation->is_in_stock();
				$variation_definition_term_id = $this->get_variation_definition_term_id( $variation, $taxonomy, $term_ids_by_slug_cache );
				if ( $variation_definition_term_id ) {
					$this->insert_lookup_table_data( $variation_id, $main_product_id, $taxonomy, $variation_definition_term_id, true, $variation_has_stock );
				} else {
					$term_ids_for_taxonomy = $data['term_ids'];
					foreach ( $term_ids_for_taxonomy as $term_id ) {
						$this->insert_lookup_table_data( $variation_id, $main_product_id, $taxonomy, $term_id, true, $variation_has_stock );
					}
				}
			}
		}
	}

	/**
	 * Get a cache of term ids by slug for a set of taxonomies, with this format:
	 *
	 * [
	 *   'taxonomy' => [
	 *     'slug_1' => id_1,
	 *     'slug_2' => id_2,
	 *     ...
	 *   ], ...
	 * ]
	 *
	 * @param array $taxonomies List of taxonomies to build the cache for.
	 * @return array A dictionary of taxonomies => dictionary of term slug => term id.
	 */
	private function get_term_ids_by_slug_cache( $taxonomies ) {
		$result = array();
		foreach ( $taxonomies as $taxonomy ) {
			$terms               = WC()->call_function(
				'get_terms',
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'id=>slug',
				)
			);
			$result[ $taxonomy ] = array_flip( $terms );
		}
		return $result;
	}

	/**
	 * Get the id of the term that defines a variation for a given taxonomy,
	 * or null if there's no such defining id (for variations having "Any <taxonomy>" as the definition)
	 *
	 * @param \WC_Product_Variation $variation The variation to get the defining term id for.
	 * @param string                $taxonomy The taxonomy to get the defining term id for.
	 * @param array                 $term_ids_by_slug_cache A term ids by slug as generated by get_term_ids_by_slug_cache.
	 * @return int|null The term id, or null if there's no defining id for that taxonomy in that variation.
	 */
	private function get_variation_definition_term_id( \WC_Product_Variation $variation, string $taxonomy, array $term_ids_by_slug_cache ) {
		$variation_attributes = $variation->get_attributes();
		$term_slug            = ArrayUtil::get_value_or_default( $variation_attributes, $taxonomy );
		if ( $term_slug ) {
			return $term_ids_by_slug_cache[ $taxonomy ][ $term_slug ];
		} else {
			return null;
		}
	}

	/**
	 * Get the variations of a given variable product.
	 *
	 * @param \WC_Product_Variable $product The product to get the variations for.
	 * @return array An array of WC_Product_Variation objects.
	 */
	private function get_variations_of( \WC_Product_Variable $product ) {
		$variation_ids = $product->get_children();
		return array_map(
			function( $id ) {
				return WC()->call_function( 'wc_get_product', $id );
			},
			$variation_ids
		);
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
			if ( ! $attribute_data->get_id() ) {
				// Custom product attribute, not suitable for attribute-based filtering.
				continue;
			}

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

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . $this->lookup_table_name . ' (
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
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
}
