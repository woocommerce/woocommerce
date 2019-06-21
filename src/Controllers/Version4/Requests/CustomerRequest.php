<?php
/**
 * Convert data in the customer schema format to a product object.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Requests;

defined( 'ABSPATH' ) || exit;

/**
 * CustomerRequest class.
 */
class CustomerRequest extends AbstractObjectRequest {

	/**
	 * Convert request to object.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the customer is invalid.
	 * @return \WC_Customer
	 */
	public function prepare_object() {
		$id     = (int) $this->get_param( 'id', 0 );
		$object = new \WC_Customer( $id );

		if ( $id !== $object->get_id() ) {
			throw new \WC_REST_Exception( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), 400 );
		}

		$this->set_props( $object );
		$this->set_meta_data( $object );

		return $object;
	}

	/**
	 * Set order props.
	 *
	 * @throws \WC_REST_Exception Will throw an exception if the customer is invalid.
	 * @param \WC_Customer $object Order object reference.
	 */
	protected function set_props( &$object ) {
		$props = [
			'username',
			'password',
			'email',
			'first_name',
			'last_name',
			'billing',
			'shipping',
			'billing',
			'shipping',
		];

		$request_props = array_intersect_key( $this->request, array_flip( $props ) );
		$prop_values   = [];

		foreach ( $request_props as $prop => $value ) {
			switch ( $prop ) {
				case 'email':
					if ( email_exists( $value ) && $value !== $object->get_email() ) {
						throw new \WC_REST_Exception( 'woocommerce_rest_customer_invalid_email', __( 'Email address is invalid.', 'woocommerce' ), 400 );
					}
					$prop_values[ $prop ] = $value;
					break;
				case 'username':
					if ( $object->get_id() && $value !== $object->get_username() ) {
						throw new \WC_REST_Exception( 'woocommerce_rest_customer_invalid_argument', __( "Username isn't editable.", 'woocommerce' ), 400 );
					}
					$prop_values[ $prop ] = $value;
					break;
				case 'billing':
				case 'shipping':
					$address     = $this->parse_address_field( $value, $object, $prop );
					$prop_values = array_merge( $prop_values, $address );
					break;
				default:
					$prop_values[ $prop ] = $value;
			}
		}

		foreach ( $prop_values as $prop => $value ) {
			$object->{"set_$prop"}( $value );
		}
	}

	/**
	 * Parse address data.
	 *
	 * @param array        $data  Posted data.
	 * @param \WC_Customer $object Customer object.
	 * @param string       $type   Address type.
	 * @return array
	 */
	protected function parse_address_field( $data, $object, $type = 'billing' ) {
		$address = [];
		foreach ( $data as $key => $value ) {
			if ( is_callable( array( $object, "set_{$type}_{$key}" ) ) ) {
				$address[ "{$type}_{$key}" ] = $value;
			}
		}
		return $address;
	}
}
