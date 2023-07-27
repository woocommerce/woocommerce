<?php
/**
 * Order refund. Refunds are based on orders (essentially negative orders) and
 * contain much of the same data.
 *
 * @version 3.0.0
 * @package WooCommerce\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order refund class.
 */
class WC_Order_Refund extends WC_Abstract_Order {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'order-refund';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'order_refund';

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'amount'           => '',
		'reason'           => '',
		'refunded_by'      => 0,
		'refunded_payment' => false,
	);

	/**
	 * List of properties that were earlier managed by data store. However, since DataStore is a not a stored entity in itself, they used to store data in metadata of the data object.
	 * With custom tables, some of these are moved from metadata to their own columns, but existing code will still try to add them to metadata. This array is used to keep track of such properties.
	 *
	 * Only reason to add a property here is that you are moving properties from DataStore instance to data object. Otherwise, if you are adding a new property, consider adding it to $data array instead.
	 *
	 * @var array
	 */
	protected $legacy_datastore_props = array(
		'_refund_amount',
		'_refund_reason',
		'_refunded_by',
		'_refunded_payment',
	);


	/**
	 * Get internal type (post type.)
	 *
	 * @return string
	 */
	public function get_type() {
		return 'shop_order_refund';
	}

	/**
	 * Get status - always completed for refunds.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return 'completed';
	}

	/**
	 * Get a title for the new post type.
	 */
	public function get_post_title() {
		// @codingStandardsIgnoreStart
		return sprintf( __( 'Refund &ndash; %s', 'woocommerce' ), (new DateTime('now'))->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'woocommerce' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Get refunded amount.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int|float
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get refund reason.
	 *
	 * @since 2.2
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string
	 */
	public function get_reason( $context = 'view' ) {
		return $this->get_prop( 'reason', $context );
	}

	/**
	 * Get ID of user who did the refund.
	 *
	 * @since 3.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_refunded_by( $context = 'view' ) {
		return $this->get_prop( 'refunded_by', $context );
	}

	/**
	 * Return if the payment was refunded via API.
	 *
	 * @since  3.3
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_refunded_payment( $context = 'view' ) {
		return $this->get_prop( 'refunded_payment', $context );
	}

	/**
	 * Get formatted refunded amount.
	 *
	 * @since 2.4
	 * @return string
	 */
	public function get_formatted_refund_amount() {
		return apply_filters( 'woocommerce_formatted_refund_amount', wc_price( $this->get_amount(), array( 'currency' => $this->get_currency() ) ), $this );
	}

	/**
	 * Set refunded amount.
	 *
	 * @param string $value Value to set.
	 * @throws WC_Data_Exception Exception if the amount is invalid.
	 */
	public function set_amount( $value ) {
		$this->set_prop( 'amount', wc_format_decimal( $value ) );
	}

	/**
	 * Set refund reason.
	 *
	 * @param string $value Value to set.
	 * @throws WC_Data_Exception Exception if the amount is invalid.
	 */
	public function set_reason( $value ) {
		$this->set_prop( 'reason', $value );
	}

	/**
	 * Set refunded by.
	 *
	 * @param int $value Value to set.
	 * @throws WC_Data_Exception Exception if the amount is invalid.
	 */
	public function set_refunded_by( $value ) {
		$this->set_prop( 'refunded_by', absint( $value ) );
	}

	/**
	 * Set if the payment was refunded via API.
	 *
	 * @since 3.3
	 * @param bool $value Value to set.
	 */
	public function set_refunded_payment( $value ) {
		$this->set_prop( 'refunded_payment', (bool) $value );
	}

	/**
	 * Override to add meta data.
	 *
	 * This omits the part of the parent method that looks for a setter function, because the setter
	 * functions that exist in this class don't actually set meta data.
	 *
	 * @since 8.1.0
	 *
	 * @param string       $key Meta key.
	 * @param string|array $value Meta value.
	 * @param bool         $unique Should this be a unique key?.
	 */
	public function add_meta_data( $key, $value, $unique = false ) {
		// Only bypass the setter function check for props with known conflicts.
		if ( ! in_array( $key, $this->legacy_datastore_props, true ) ) {
			return parent::add_meta_data( $key, $value, $unique );
		}

		$this->maybe_read_meta_data();
		if ( $unique ) {
			$this->delete_meta_data( $key );
		}
		$this->meta_data[] = new WC_Meta_Data(
			array(
				'key'   => $key,
				'value' => $value,
			)
		);
	}

	/**
	 * Override to update meta data by key or ID, if provided.
	 *
	 * This omits the part of the parent method that looks for a setter function, because the setter
	 * functions that exist in this class don't actually set meta data.
	 *
	 * @since  8.1.0
	 *
	 * @param  string       $key Meta key.
	 * @param  string|array $value Meta value.
	 * @param  int          $meta_id Meta ID.
	 */
	public function update_meta_data( $key, $value, $meta_id = 0 ) {
		// Only bypass the setter function check for props with known conflicts.
		if ( ! in_array( $key, $this->legacy_datastore_props, true ) ) {
			return parent::update_meta_data( $key, $value, $meta_id );
		}

		$this->maybe_read_meta_data();

		$array_key = false;

		if ( $meta_id ) {
			$array_keys = array_keys( wp_list_pluck( $this->meta_data, 'id' ), $meta_id, true );
			$array_key  = $array_keys ? current( $array_keys ) : false;
		} else {
			// Find matches by key.
			$matches = array();
			foreach ( $this->meta_data as $meta_data_array_key => $meta ) {
				if ( $meta->key === $key ) {
					$matches[] = $meta_data_array_key;
				}
			}

			if ( ! empty( $matches ) ) {
				// Set matches to null so only one key gets the new value.
				foreach ( $matches as $meta_data_array_key ) {
					$this->meta_data[ $meta_data_array_key ]->value = null;
				}
				$array_key = current( $matches );
			}
		}

		if ( false !== $array_key ) {
			$meta        = $this->meta_data[ $array_key ];
			$meta->key   = $key;
			$meta->value = $value;
		} else {
			$this->add_meta_data( $key, $value, true );
		}
	}

	/**
	 * Magic __get method for backwards compatibility.
	 *
	 * @param string $key Value to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		wc_doing_it_wrong( $key, 'Refund properties should not be accessed directly.', '3.0' );
		/**
		 * Maps legacy vars to new getters.
		 */
		if ( 'reason' === $key ) {
			return $this->get_reason();
		} elseif ( 'refund_amount' === $key ) {
			return $this->get_amount();
		}
		return parent::__get( $key );
	}

	/**
	 * Gets an refund from the database.
	 *
	 * @deprecated 3.0
	 * @param int $id (default: 0).
	 * @return bool
	 */
	public function get_refund( $id = 0 ) {
		wc_deprecated_function( 'get_refund', '3.0', 'read' );

		if ( ! $id ) {
			return false;
		}

		$result = wc_get_order( $id );

		if ( $result ) {
			$this->populate( $result );
			return true;
		}

		return false;
	}

	/**
	 * Get refund amount.
	 *
	 * @deprecated 3.0
	 * @return int|float
	 */
	public function get_refund_amount() {
		wc_deprecated_function( 'get_refund_amount', '3.0', 'get_amount' );
		return $this->get_amount();
	}

	/**
	 * Get refund reason.
	 *
	 * @deprecated 3.0
	 * @return string
	 */
	public function get_refund_reason() {
		wc_deprecated_function( 'get_refund_reason', '3.0', 'get_reason' );
		return $this->get_reason();
	}
}
