<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Data Store Interface
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Object_Data_Store {

	/**
	 * Method to create a new record of a WC_Data based object.
	 * @param WC_Data
	 */
	public function create( &$data );

	/**
	 * Method to read a record. Creates a new WC_Data based object.
	 * @param WC_Data
	 */
	public function read( &$data );

	/**
	 * Updates a record in the database.
	 * @param WC_Data
	 */
	public function update( &$data );

	/**
	 * Deletes a record from the database.
	 * @param  WC_Data
	 * @param  array $args Array of args to pass to the delete method.
	 * @return bool result
	 */
	public function delete( &$data, $args = array() );
}
