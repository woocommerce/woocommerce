<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Grouped Product Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Grouped_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface {

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 *
	 * @param WC_Product
	 * @param bool $force Force all props to be written even if not changed. This is used during creation.
	 * @since 2.7.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
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

		parent::update_post_meta( $product, $force );
	}

	/**
	 * Sync grouped product prices with children.
	 *
	 * @since 2.7.0
	 * @param WC_Product|int $product
	 */
	public function sync_price( &$product ) {
		global $wpdb;

		$children_ids = get_posts( array(
			'post_parent' => $product->get_id(),
			'post_type'   => 'product',
			'fields'      => 'ids',
		) );
		$prices = $children_ids ? array_unique( $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_price' AND post_id IN ( " . implode( ',', array_map( 'absint', $children_ids ) ) . " )" ) ) : array();

		delete_post_meta( $product->get_id(), '_price' );
		delete_transient( 'wc_var_prices_' . $product->get_id() );

		if ( $prices ) {
			sort( $prices );
			// To allow sorting and filtering by multiple values, we have no choice but to store child prices in this manner.
			foreach ( $prices as $price ) {
				add_post_meta( $product->get_id(), '_price', $price, false );
			}
		}
	}
}
