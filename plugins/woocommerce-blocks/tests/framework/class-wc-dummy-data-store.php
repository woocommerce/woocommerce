<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Dummy Data Store: CPT.
 *
 * Used to test swapping out data stores.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Dummy_Data_Store_CPT implements WC_Object_Data_Store_Interface {
	public function create( &$data ) { }
	public function read( &$data ) { }
	public function update( &$data ) { }
	public function delete( &$data, $args = array() ) { }
	public function read_meta( &$data ) { }
	public function delete_meta( &$data, $meta ) { }
	public function add_meta( &$data, $meta ) { }
	public function update_meta( &$data, $meta ) { }
}

/**
 * WC Dummy Data Store: Custom Table.
 *
 * Used to test swapping out data stores.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Dummy_Data_Store_Custom_Table implements WC_Object_Data_Store_Interface {
	public function create( &$data ) { }
	public function read( &$data ) { }
	public function update( &$data ) { }
	public function delete( &$data, $args = array() ) { }
	public function read_meta( &$data ) { }
	public function delete_meta( &$data, $meta ) { }
	public function add_meta( &$data, $meta ) { }
	public function update_meta( &$data, $meta ) { }
}
