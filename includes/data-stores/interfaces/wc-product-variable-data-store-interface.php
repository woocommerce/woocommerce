<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Variable Data Store Interface
 *
 * Functions that must be defined by product variable store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Product_Variable_Data_Store_Interface {
	/**
	 * Does a child have a weight set?
	 *
	 * @param WC_Product
	 * @return boolean
	 */
	public function child_has_weight( $product );

	/**
	 * Does a child have dimensions set?
	 *
	 * @param WC_Product
	 * @return boolean
	 */
	public function child_has_dimensions( $product );

	/**
	 * Is a child in stock?
	 *
	 * @param WC_Product
	 * @return boolean
	 */
	public function child_is_in_stock( $product );

	/**
	 * Stock managed at the parent level - update children being managed by this product.
	 * This sync function syncs downwards (from parent to child) when the variable product is saved.
	 *
	 * @param WC_Product
	 */
	public function sync_managed_variation_stock_status( &$product );

	/**
	 * Sync variable product prices with children.
	 *
	 * @param WC_Product|int $product
	 */
	public function sync_price( &$product );
}
