<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared logic for post/CPT data stores.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Data_Store_CPT {

	/**
	 * Get and store terms from a taxonomy.
	 *
	 * @since  2.7.0
	 * @param  WC_Product
	 * @param  string $taxonomy Taxonomy name e.g. product_cat
	 * @return array of terms
	 */
	protected function get_term_ids( $product, $taxonomy ) {
		$terms = get_the_terms( $product->get_id(), $taxonomy );
		if ( false === $terms || is_wp_error( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'term_id' );
	}

}
