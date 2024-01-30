<?php
/**
 * Class WC_Customer_Data_Store_Session file.
 *
 * @package WooCommerce\DataStores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store which stores the data in session.
 *
 * Used by the WC_Customer class to store customer data to the session without persisting it to the database.
 *
 * @version  3.0.0
 */
class WC_Customer_Data_Store_Session extends WC_Customer_Data_Store {

	/**
	 * Keys which are stored to the customer session.
	 *
	 * @var array
	 */
	protected $session_keys = array(
		'id',
		'date_modified',
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address',
		'billing_address_2',
		'billing_state',
		'billing_country',
		'shipping_postcode',
		'shipping_city',
		'shipping_address_1',
		'shipping_address',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
		'is_vat_exempt',
		'calculated_shipping',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_phone',
		'meta_data',
	);

	/**
	 * Update the session. Note, this does not persist the data to the DB.
	 *
	 * @param WC_Customer $customer Customer object.
	 */
	public function create( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Update the session. Note, this does not persist the data to the DB.
	 *
	 * @param WC_Customer $customer Customer object.
	 */
	public function update( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Saves all customer data to the session.
	 *
	 * @param WC_Customer $customer Customer object.
	 */
	public function save_to_session( $customer ) {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			if ( 'meta_data' === $session_key ) {
				$session_value = wp_json_encode( $customer->get_meta_data() );
			} else {
				$session_value = $customer->{"get_$function_key"}( 'edit' );
			}
			$data[ $session_key ] = (string) $session_value;
		}
		WC()->session->set( 'customer', $data );
	}

	/**
	 * Read customer data from the session unless the user has logged in, in which case the stored ID will differ from
	 * the actual ID.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 */
	public function read( &$customer ) {
		if ( $customer->get_id() ) {
			parent::read( $customer );
		}

		$data = (array) WC()->session->get( 'customer' );

		/**
		 * There is a valid session if $data is not empty, and the ID matches the logged in user ID.
		 *
		 * If the user object has been updated since the session was created (based on date_modified) we should not load the session - data should be reloaded.
		 */
		if ( isset( $data['id'], $data['date_modified'] ) && $data['id'] === (string) $customer->get_id() && $data['date_modified'] === (string) $customer->get_date_modified( 'edit' ) ) {
			foreach ( $this->session_keys as $session_key ) {
				if ( in_array( $session_key, array( 'id', 'date_modified' ), true ) ) {
					continue;
				}
				$function_key = $session_key;
				if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
					$session_key = str_replace( 'billing_', '', $session_key );
				}
				if ( ! empty( $data[ $session_key ] ) && is_callable( array( $customer, "set_{$function_key}" ) ) ) {
					if ( 'meta_data' === $session_key ) {
						$meta_data_values = json_decode( wp_unslash( $data[ $session_key ] ), true );
						foreach ( $meta_data_values as $meta_data_value ) {
							if ( ! isset( $meta_data_value['key'], $meta_data_value['value'] ) ) {
								continue;
							}
							$customer->add_meta_data( $meta_data_value['key'], $meta_data_value['value'], true );
						}
					} else {
						$customer->{"set_{$function_key}"}( wp_unslash( $data[ $session_key ] ) );
					}
				}
			}
		}
		$this->set_defaults( $customer );
		$customer->set_object_read( true );
	}

	/**
	 * Set default values for the customer object if props are unset.
	 *
	 * @param WC_Customer $customer Customer object.
	 */
	protected function set_defaults( &$customer ) {
		try {
			$default              = wc_get_customer_default_location();
			$has_shipping_address = $customer->has_shipping_address();

			if ( ! $customer->get_billing_country() ) {
				$customer->set_billing_country( $default['country'] );
			}

			if ( ! $customer->get_shipping_country() && ! $has_shipping_address ) {
				$customer->set_shipping_country( $customer->get_billing_country() );
			}

			if ( ! $customer->get_billing_state() ) {
				$customer->set_billing_state( $default['state'] );
			}

			if ( ! $customer->get_shipping_state() && ! $has_shipping_address ) {
				$customer->set_shipping_state( $customer->get_billing_state() );
			}

			if ( ! $customer->get_billing_email() && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$customer->set_billing_email( $current_user->user_email );
			}
		} catch ( WC_Data_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	/**
	 * Deletes the customer session.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 * @param array       $args Array of args to pass to the delete method.
	 */
	public function delete( &$customer, $args = array() ) {
		WC()->session->set( 'customer', null );
	}
}
