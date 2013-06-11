<?php

return new WC_Transient_Helper();

class WC_Transient_Helper extends WC_Helper {
	/**
	 * Clear all transients cache for product data.
	 *
	 * @access public
	 * @param int $post_id (default: 0)
	 * @return void
	 */
	public function clear_product_transients( $post_id = 0 ) {
		global $wpdb;

		$post_id = absint( $post_id );

		$wpdb->show_errors();

		// Clear core transients
		$transients_to_clear = array(
			'wc_products_onsale',
			'wc_hidden_product_ids',
			'wc_hidden_product_ids_search',
			'wc_attribute_taxonomies',
			'wc_term_counts'
		);

		foreach( $transients_to_clear as $transient ) {
			delete_transient( $transient );
		}

		// Clear transients for which we don't have the name
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_uf_pid_%') OR `option_name` LIKE ('_transient_timeout_wc_uf_pid_%')" );
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ln_count_%') OR `option_name` LIKE ('_transient_timeout_wc_ln_count_%')" );
		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_ship_%') OR `option_name` LIKE ('_transient_timeout_wc_ship_%')" );

		// Clear product specific transients
		$post_transients_to_clear = array(
			'wc_product_children_ids_',
			'wc_product_total_stock_',
			'wc_average_rating_',
			'wc_rating_count_',
			'woocommerce_product_type_', // No longer used
			'wc_product_type_', // No longer used
		);

		if ( $post_id > 0 ) {

			foreach( $post_transients_to_clear as $transient ) {
				delete_transient( $transient . $post_id );
				$wpdb->query( $wpdb->prepare( "DELETE FROM `$wpdb->options` WHERE `option_name` = %s OR `option_name` = %s", '_transient_' . $transient . $post_id, '_transient_timeout_' . $transient . $post_id ) );
			}

			clean_post_cache( $post_id );

		} else {

			foreach( $post_transients_to_clear as $transient ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE %s OR `option_name` LIKE %s", '_transient_' . $transient . '%', '_transient_timeout_' . $transient . '%' ) );
			}

		}
	}
}