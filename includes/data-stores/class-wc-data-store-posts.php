<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared logic for posts data stores.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Data_Store_Posts  {

	/**
	 * Get and store terms from a taxonomy.
	 *
	 * @since  2.7.0
	 * @param  int $id
	 * @param  string $taxonomy Taxonomy name e.g. product_cat
	 * @return array of terms
	 */
	protected function get_term_ids( $id, $taxonomy ) {
		return wp_get_post_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
	}

}
