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
	 * @return int|WP_Error New object ID or error on failure.
	 */
	public function create( $data );

	/**
	 * Method to read a record. Creates a new WC_Data based object.
	 * @param WC_Data
	 * @return WC_Data|WP_Error WC_Data based object or error on failure.
	 */
	public function read( &$data );

	/**
	 * Updates a record in the database.
	 * @param WC_Data
	 * @return true|WP_Error True on update or error on failure.
	 */
	public function update( $data );

	/**
	 * Deletes a record from the database.
	 * @todo Trashing?
	 * @param WC_Data
	 * @param bool $force_delete True to permently delete, false to trash.
	 * @return bool|WP_Error Return true on success or error on failure.
	 */
	public function delete( $data, $force_delete );
}
