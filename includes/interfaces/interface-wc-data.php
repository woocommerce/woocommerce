<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Data Interface
 *
 * Implemented by classes using the same CRUD(s) pattern.
 *
 * @version  2.6.0
 * @package  WooCommerce/Interfaces
 * @category Interfaces
 * @author   WooThemes
 */
interface WC_Data {
    /**
     * Returns the unique ID for this object.
     * @return int
     */
    public function get_id();

    /**
     * Returns all data for this object.
     * @return array
     */
    public function get_data();

    /**
     * Creates new object in the database.
     */
    public function create();

    /**
     * Read object from the database.
     */
    public function read();

    /**
     * Updates object data in the database.
     */
    public function update();

    /**
     * Updates object data in the database.
     */
    public function delete();

    /**
     * Save; should create or update based on object existance.
     */
    public function save();
}
