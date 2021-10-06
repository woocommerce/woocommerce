<?php
/**
 * Dummy WC data stores used to test data store functionality.
 *
 * @package WooCommerce\Tests\Framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Squiz.Commenting

/**
 * WC Dummy Data Store: CPT.
 *
 * Used to test swapping out data stores.
 *
 * @version  3.0.0
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

	/**
	 * Method used to test WC_Data_Store::__call().
	 *
	 * @return array
	 */
	public function custom_method( $first_param, $second_param, $third_param ) {
		return array( $first_param, $second_param, $third_param );
	}
}

/**
 * WC Dummy Data Store: Custom Table.
 *
 * Used to test swapping out data stores.
 *
 * @version  3.0.0
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

// phpcs:enable
