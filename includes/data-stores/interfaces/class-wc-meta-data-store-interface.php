<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Meta Data Store Interface
 *
 * Functions that must be defined by meta store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooCommerce
 */
interface WC_Meta_Data_Store_Interface {
	/**
	 * Returns an array of objects containing meta_id, meta_key, and meta_value.
	 * @param  WC_Data $object
	 * @return array
	 */
	public function read_meta( $object );

	/**
	 * Deletes a piece of meta.
	 * @param WC_Data $object
	 * @param object $meta Object containing ->id, ->key, and possibily ->value.
	 */
	public function delete_meta( $object, $meta );

	/**
	 * Adds meta.
	 * @param  WC_Data $object
	 * @param  object $meta Object containing ->key and ->value
	 * @return int Meta ID
	 */
	public function add_meta( $object, $meta );

	/**
	 * Updates meta.
	 * @param WC_Data $object
	 * @param object $meta Object containing ->id, ->key and ->value
	 */
	public function update_meta( $object, $meta );
}
