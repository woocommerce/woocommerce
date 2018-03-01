<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Grouped Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Grouped_Data_Store_CPT extends WC_Product_Data_Store_CPT implements WC_Object_Data_Store_Interface {

	/**
	 * Helper method that updates all the post meta for a grouped product.
	 *
	 * @param WC_Product
	 * @param bool Force update. Used during create.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$product, $force = false ) {
		$meta_key_to_props = array(
			'_children' => 'children',
		);

		$props_to_update = $force ? $meta_key_to_props : $this->get_props_to_update( $product, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value   = $product->{"get_$prop"}( 'edit' );
			$updated = update_post_meta( $product->get_id(), $meta_key, $value );
			if ( $updated ) {
				$this->updated_props[] = $prop;
			}
		}

		parent::update_post_meta( $product, $force );
	}

	/**
	 * Handle updated meta props after updating meta data.
	 *
	 * @since  3.0.0
	 * @param  WC_Product $product
	 */
	protected function handle_updated_props( &$product ) {
		if ( in_array( 'children', $this->updated_props ) ) {
			$this->update_prices_from_children( $product );
		}
		parent::handle_updated_props( $product );
	}

	/**
	 * Sync grouped product prices with children.
	 *
	 * @since 3.0.0
	 * @param WC_Product|int $product
	 */
	public function sync_price( &$product ) {
		$this->update_prices_from_children( $product );
	}

	/**
	 * Loop over child products and update the grouped product prices.
	 *
	 * @param WC_Product $product Product object.
	 */
	protected function update_prices_from_children( &$product ) {
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
	}
}
