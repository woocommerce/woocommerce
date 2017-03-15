<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store which stores the data in session.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Customer_Data_Store_Session extends WC_Data_Store_WP implements WC_Customer_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 * @var array
	 */
	protected $session_keys = array(
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
	);

	/**
	 * Simply update the session.
	 *
	 * @param WC_Customer
	 */
	public function create( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Simply update the session.
	 *
	 * @param WC_Customer
	 */
	public function update( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Saves all customer data to the session.
	 *
	 * @param WC_Customer
	 */
	public function save_to_session( $customer ) {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key = $session_key;
			if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
				$session_key = str_replace( 'billing_', '', $session_key );
			}
			$data[ $session_key ] = $customer->{"get_$function_key"}( 'edit' );
		}
		if ( WC()->session->get( 'customer' ) !== $data ) {
			WC()->session->set( 'customer', $data );
		}
	}

	/**
	 * Read customer data from the session.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 */
	public function read( &$customer ) {
		$data = (array) WC()->session->get( 'customer' );
		if ( ! empty( $data ) ) {
			foreach ( $this->session_keys as $session_key ) {
				$function_key = $session_key;
				if ( 'billing_' === substr( $session_key, 0, 8 ) ) {
					$session_key = str_replace( 'billing_', '', $session_key );
				}
				if ( ! empty( $data[ $session_key ] ) && is_callable( array( $customer, "set_{$function_key}" ) ) ) {
					$customer->{"set_{$function_key}"}( $data[ $session_key ] );
				}
			}
		}
		$this->set_defaults( $customer );
		$customer->set_object_read( true );
	}

	/**
	 * Load default values if props are unset.
	 *
	 * @param WC_Customer
	 */
	protected function set_defaults( &$customer ) {
		$default = wc_get_customer_default_location();

		if ( ! $customer->get_billing_country() ) {
			$customer->set_billing_country( $default['country'] );
		}

		if ( ! $customer->get_shipping_country() ) {
			$customer->set_shipping_country( $customer->get_billing_country() );
		}

		if ( ! $customer->get_billing_state() ) {
			$customer->set_billing_state( $default['state'] );
		}

		if ( ! $customer->get_shipping_state() ) {
			$customer->set_shipping_state( $customer->get_billing_state() );
		}

		if ( ! $customer->get_billing_email() && is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$customer->set_billing_email( $current_user->user_email );
		}
	}

	/**
	 * Deletes a customer from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$customer, $args = array() ) {
		WC()->session->set( 'customer', null );
	}

	/**
	 * Gets the customers last order.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return WC_Order|false
	 */
	public function get_last_order( &$customer ) {
		return false;
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return integer
	 */
	public function get_order_count( &$customer ) {
		return 0;
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return float
	 */
	public function get_total_spent( &$customer ) {
		return 0;
	}
}
