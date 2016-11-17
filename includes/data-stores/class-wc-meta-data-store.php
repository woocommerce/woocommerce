<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta data store.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Meta_Data_Store implements WC_Meta_Data_Store_Interface {

	/**
	 * Returns an array of objects containing meta_id, meta_key, and meta_value.
	 */
	public function read_meta( $object ) {
		global $wpdb;
		$db_info = $this->get_db_info( $object );
		return $wpdb->get_results( $wpdb->prepare( "
			SELECT " . $db_info['meta_id_field'] . " as meta_id, meta_key, meta_value
			FROM " . $db_info['table'] . "
			WHERE " . $db_info['object_id_field'] . "=%d AND meta_key NOT LIKE 'wp\_%%' ORDER BY " . $db_info['meta_id_field'] . "
		", $object->get_id() ) );
	}

	/**
	 * Deletes a piece of meta.
	 *
	 * @since 2.7.0
	 * @param WC_Data $object
	 * @param object $meta Object containing ->id, ->key, and possibily ->value.
	 */
	public function delete_meta( $object, $meta ) {
		delete_metadata_by_mid( $object->get_meta_type(), $meta->id );
	}

	/**
	 * Adds meta.
	 *
	 * @since 2.7.0
	 * @param WC_Data $object
	 * @param object $meta Object containing ->key and ->value
	 * @return int Meta ID
	 */
	public function add_meta( $object, $meta ) {
		return add_metadata( $object->get_meta_type(), $object->get_id(), $meta->key, $meta->value, false );
	}

	/**
	 * Updates meta.
	 *
	 * @since 2.7.0
	 * @param WC_Data $object
	 * @param object $meta Object containing ->id, ->key and ->value
	 */
	public function update_meta( $object, $meta ) {
		update_metadata_by_mid( $object->get_meta_type(), $meta->id, $meta->value, $meta->key );
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 *
	 * @since  2.7.0
	 * @param  WC_Data
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info( &$object ) {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $object->get_meta_type(), array( 'post', 'user', 'comment', 'term' ) ) ) {
			$table .= 'woocommerce_';
		}

		$table          .= $object->get_meta_type() . 'meta';
		$object_id_field = $object->get_meta_type() . '_id';

		// Figure out our field names.
		if ( 'user' === $object->get_meta_type() ) {
			$meta_id_field = 'umeta_id';
		}

		if ( ! empty( $object->get_object_id_field_for_meta() ) ) {
			$object_id_field = $object->get_object_id_field_for_meta();
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

}
