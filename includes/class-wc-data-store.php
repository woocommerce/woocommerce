<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Data Store.
 *
 * @since    2.7.0
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Data_Store {

	/**
	 * Contains an instance of the data store class that we are working with.
	 */
	private $instance = null;

	/**
	 * Contains the name of the current data store's class name.
	 */
	private $current_class_name = '';

	/**
	 * Contains an array of WC supported data stores.
	 * Format of object name => class name.
	 * You can aso pass something like product_<type> for product stores and
	 * that type will be looked at, and then fall back to 'product'.
	 */
	private $stores = array(
		'product' => 'WC_Product_Data_Store_Posts',
	);

	/**
	 * Tells WC_Data_Store which object (coupon, product, order, etc)
	 * store we want to work with.
	 *
	 * @param string $object_type Name of object.
	 */
	public function __construct( $object_type ) {
		$this->stores = apply_filters( 'woocommerce_data_stores', $this->stores );

		// If this objec type can't be found, check to see if we can load one
		// level up (so if product_type isn't found, we try product).
		if ( ! array_key_exists( $object_type, $this->stores ) ) {
			$pieces = explode( '_', $object_type );
			$object_type = $pieces[0];
		}

		if ( array_key_exists( $object_type, $this->stores ) ) {
			$store = apply_filters( 'woocommerce_' . $object_type . '_data_store', $this->stores[ $object_type ] );
			if ( ! class_exists( $store ) ) {
				throw new Exception( __( 'Invalid data store.', 'woocommerce' ) );
			}
			$this->current_class_name = $store;
			$this->instance           = new $store;
		} else {
			throw new Exception( __( 'Invalid data store.', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Loads a data store for us or returns null if an invalid store.
	 *
	 * @param string $object_type Name of object.
	 * @since 2.7.0
	 */
	public static function load( $object_type ) {
		try {
			return new WC_Data_Store( $object_type );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Returns the class name of the current data store.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_current_class_name() {
		return $this->current_class_name;
	}

	/**
	 * Reads an object from the data store.
	 */
	public function read( &$data ) {
		return $this->instance->read( $data );
	}

}
