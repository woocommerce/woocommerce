<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services;

use WC_Customer;
use WC_Data;
use WC_Order;

/**
 * Reads, writes, and syncs additional checkout field values stored as order and customer meta.
 *
 * Used by CheckoutFields, which owns the field definitions and validation; this trait only covers
 * where values are persisted and how they are read back.
 */
trait CheckoutFieldsStorage {

	/**
	 * Persists a field value for a given order. This would also optionally set the field value on the customer object if the order is linked to a registered customer.
	 *
	 * @param string   $key The field key.
	 * @param mixed    $value The field value.
	 * @param WC_Order $order The order to persist the field for.
	 * @param string   $group The group to persist the field for (shipping|billing|other).
	 * @param bool     $set_customer Whether to set the field value on the customer or not.
	 *
	 * @return void
	 */
	public function persist_field_for_order( string $key, $value, WC_Order $order, string $group = 'other', bool $set_customer = true ) {
		$group = $this->prepare_group_name( $group );
		$this->set_array_meta( $key, $value, $order, $group );
		if ( $set_customer && $order->get_customer_id() ) {
			$customer = new WC_Customer( $order->get_customer_id() );
			$this->persist_field_for_customer( $key, $value, $customer, $group );
		}
	}

	/**
	 * Persists a field value for a given customer.
	 *
	 * @param string      $key The field key.
	 * @param mixed       $value The field value.
	 * @param WC_Customer $customer The customer to persist the field for.
	 * @param string      $group The group to persist the field for (shipping|billing|other).
	 *
	 * @return void
	 */
	public function persist_field_for_customer( string $key, $value, WC_Customer $customer, string $group = 'other' ) {
		$group = $this->prepare_group_name( $group );
		$this->set_array_meta( $key, $value, $customer, $group );
	}

	/**
	 * Sets a field value in an array meta, supporting routing things to billing, shipping, or additional fields, based on a prefix for the key.
	 *
	 * @param string               $key The field key.
	 * @param mixed                $value The field value.
	 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
	 * @param string               $group The group to set the field value for (shipping|billing|other).
	 *
	 * @return void
	 */
	private function set_array_meta( string $key, $value, WC_Data $wc_object, string $group ) {
		$meta_key = self::get_group_key( $group ) . $key;

		/**
		 * Allow reacting for saving an additional field value.
		 *
		 * @param string               $key The key of the field being saved.
		 * @param mixed                $value The value of the field being saved.
		 * @param string               $group The group of this location (shipping|billing|other).
		 * @param WC_Customer|WC_Order $wc_object The object to set the field value for.
		 *
		 * @since 8.9.0
		 */
		do_action( 'woocommerce_set_additional_field_value', $key, $value, $group, $wc_object );
		// Convert boolean values to strings because Data Stores will skip false values.
		if ( is_bool( $value ) ) {
			$value = $value ? '1' : '0';
		}
		$wc_object->update_meta_data( $meta_key, $value );
	}

	/**
	 * Returns a field value for a given object.
	 *
	 * @param string               $key The field key.
	 * @param WC_Customer|WC_Order $wc_object The customer or order to get the field value for.
	 * @param string               $group The group to get the field value for (shipping|billing|other).
	 *
	 * @return mixed The field value.
	 */
	public function get_field_from_object( string $key, WC_Data $wc_object, string $group = 'other' ) {
		$group    = $this->prepare_group_name( $group );
		$meta_key = self::get_group_key( $group ) . $key;
		$value    = $wc_object->get_meta( $meta_key, true );

		if ( ! $value && '0' !== $value ) {
			/**
			 * Allow providing a default value for additional fields if no value is already set.
			 *
			 * @param null $value The default value for the filter, always null.
			 * @param string $group The group of this key (shipping|billing|other).
			 * @param WC_Data $wc_object The object to get the field value for.
			 *
			 * @since 8.9.0
			 */
			$value = apply_filters( "woocommerce_get_default_value_for_{$key}", null, $group, $wc_object );
		}

		// We cast the value to a boolean if the field is a checkbox.
		if ( $this->is_field( $key ) && 'checkbox' === $this->additional_fields[ $key ]['type'] ) {
			return '1' === $value;
		}

		if ( null === $value ) {
			return '';
		}

		return $value;
	}

	/**
	 * Returns an array of all fields values for a given object in a group.
	 *
	 * @param WC_Data $wc_object The object or order to get the fields for.
	 * @param string  $group The group to get the fields for (shipping|billing|other).
	 * @param bool    $all Whether to return all fields or only the ones that are still registered. Default false.
	 * @return array An array of fields.
	 */
	public function get_all_fields_from_object( WC_Data $wc_object, string $group = 'other', bool $all = false ) {
		$meta_data = [];
		$group     = $this->prepare_group_name( $group );
		$prefix    = self::get_group_key( $group );

		$meta = $wc_object->get_meta_data();
		foreach ( $meta as $meta_data_object ) {
			if ( 0 === \strpos( $meta_data_object->key, $prefix ) ) {
				$key = \str_replace( $prefix, '', $meta_data_object->key );
				if ( $all || $this->is_field( $key ) ) {
					$meta_data[ $key ] = $meta_data_object->value;
				}
			}
		}

		$missing_fields = array_diff( array_keys( $this->get_fields_for_group( $group ) ), array_keys( $meta_data ) );

		foreach ( $missing_fields as $missing_field ) {
				/**
				 * Allow providing a default value for additional fields if no value is already set.
				 *
				 * @param null $value The default value for the filter, always null.
				 * @param string $group The group of this key (shipping|billing|other).
				 * @param WC_Data $wc_object The object to get the field value for.
				 *
				 * @since 8.9.0
				 */
				$value = apply_filters( "woocommerce_get_default_value_for_{$missing_field}", null, $group, $wc_object );

			if ( isset( $value ) ) {
				$meta_data[ $missing_field ] = $value;
			}
		}

		return $meta_data;
	}

	/**
	 * Copies additional fields from an order to a customer.
	 *
	 * @param WC_Order    $order The order to sync the fields for.
	 * @param WC_Customer $customer The customer to sync the fields for.
	 *
	 * @return void
	 */
	public function sync_customer_additional_fields_with_order( WC_Order $order, WC_Customer $customer ) {
		foreach ( $this->groups as $group ) {
			$order_additional_fields = $this->get_all_fields_from_object( $order, $group, true );

			// Sync customer additional fields with order additional fields.
			foreach ( $order_additional_fields as $key => $value ) {
				if ( $this->is_customer_field( $key ) ) {
					$this->persist_field_for_customer( $key, $value, $customer, $group );
				}
			}
		}
	}

	/**
	 * Copies additional fields from a customer to an order.
	 *
	 * @param WC_Order    $order The order to sync the fields for.
	 * @param WC_Customer $customer The customer to sync the fields for.
	 *
	 * @return void
	 */
	public function sync_order_additional_fields_with_customer( WC_Order $order, WC_Customer $customer ) {
		foreach ( $this->groups as $group ) {
			$customer_additional_fields = $this->get_all_fields_from_object( $customer, $group, true );

			// Sync order additional fields with customer additional fields.
			foreach ( $customer_additional_fields as $key => $value ) {
				if ( $this->is_field( $key ) ) {
					$this->persist_field_for_order( $key, $value, $order, $group, false );
				}
			}
		}
	}
}
