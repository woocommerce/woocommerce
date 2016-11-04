<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Product Dummy Data Store.
 *
 * Used to test swapping out data stores.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Data_Store_Dummy implements WC_Object_Data_Store {
	public function create( $data ) { }
	public function read( $data ) { }
	public function update( $data ) { }
	public function delete( $data, $force_delete = false ) { }
}
