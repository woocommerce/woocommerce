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
 * Used by the WC_Customer class to store customer data to the session.
 *
 * @version  3.0.0
 */
class WC_Customer_Data_Store_Session extends WC_Data_Store_WP implements WC_Customer_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 *
	 * @var array
	 */
	protected $session_keys = array(
		'id',
		'date_modified',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'billing_address',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
		'billing_country',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_phone',
		'shipping_address',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_city',
		'shipping_state',
		'shipping_postcode',
		'shipping_country',
		'is_vat_exempt',
		'calculated_shipping',
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
		if ( ! WC()->session ) {
			wc_doing_it_wrong(
				__METHOD__,
				__( 'WC_Session is not available, customer data cannot be saved to session.', 'woocommerce' ),
				'9.8.0'
			);
			return;
		}

		$data = $this->get_customer_session_data( $customer );

		if ( $this->is_default_customer_data( $data ) ) {
			// Clear the customer from the session if it matches the default.
			WC()->session->set( 'customer', null );
		} else {
			WC()->session->set( 'customer', $data );
		}
	}

	/**
	 * Read customer data from the session unless the user has logged in, in which case the stored ID will differ from
	 * the actual ID.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 */
	public function read( &$customer ) {
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
						if ( is_array( $data[ $session_key ] ) ) {
							foreach ( $data[ $session_key ] as $meta_data_value ) {
								if ( ! isset( $meta_data_value['key'], $meta_data_value['value'] ) ) {
									continue;
								}
								$customer->add_meta_data( $meta_data_value['key'], $meta_data_value['value'], true );
							}
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

	/**
	 * Gets the customers last order.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 * @return WC_Order|false
	 */
	public function get_last_order( &$customer ) {
		return false;
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 * @return integer
	 */
	public function get_order_count( &$customer ) {
		return 0;
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @since 3.0.0
	 * @param WC_Customer $customer Customer object.
	 * @return float
	 */
	public function get_total_spent( &$customer ) {
		return 0;
	}

	/**
	 * Get the customer data to store in the session.
	 *
	 * @param WC_Customer $customer Customer object.
	 * @return array
	 */
	private function get_customer_session_data( $customer ) {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			if ( 'meta_data' === $session_key ) {
				/**
				 * Filter the allowed session meta data keys.
				 *
				 * If the customer object contains any meta data with these keys, it will be stored within the WooCommerce session.
				 *
				 * @since 8.7.0
				 * @param array $allowed_keys The allowed meta data keys.
				 * @param WC_Customer $customer The customer object.
				 */
				$allowed_keys = apply_filters( 'woocommerce_customer_allowed_session_meta_keys', array(), $customer );

				$session_value = array();
				foreach ( $customer->get_meta_data() as $meta_data ) {
					if ( in_array( $meta_data->key, $allowed_keys, true ) ) {
						$session_value[] = array(
							'key'   => $meta_data->key,
							'value' => $meta_data->value,
						);
					}
				}
				$data['meta_data'] = $session_value;
			} else {
				$session_value        = $customer->{"get_$function_key"}( 'edit' );
				$data[ $session_key ] = (string) $session_value;
			}
		}

		return $data;
	}

	/**
	 * Returns whether the customer data is the same as a default customer.
	 *
	 * @param array $customer_data The customer data to check.
	 * @return bool
	 */
	private function is_default_customer_data( array $customer_data ): bool {
		$default_customer = new WC_Customer();
		$this->set_defaults( $default_customer );

		return $customer_data === $this->get_customer_session_data( $default_customer );
	}
}
