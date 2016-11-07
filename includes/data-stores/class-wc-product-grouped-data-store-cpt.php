<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Variable Product Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Grouped_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store {

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 */
	protected function update_post_meta( $product ) {
		if ( update_post_meta( $product->get_id(), '_children', $product->get_children( 'edit' ) ) ) {
			$child_prices = array();
			foreach ( $product->get_children( 'edit' ) as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( $child ) {
					$child_prices[] = $child->get_price();
				}
			}
			$child_prices = array_filter( $child_prices );
			delete_post_meta( $product->get_id(), '_price' );

			if ( ! empty( $child_prices ) ) {
				add_post_meta( $product->get_id(), '_price', min( $child_prices ) );
				add_post_meta( $product->get_id(), '_price', max( $child_prices ) );
			}

			$this->extra_data_saved = true;
		}

		parent::update_post_meta( $product );
	}

}
