<?php
/**
 * UpdateUtils class.
 *
 * @package WooCommerce\RestApi
 * @internal This file is for internal use only and should not be used by external code.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers;

defined( 'ABSPATH' ) || exit;

use WC_Customer;
use WC_REST_Exception;
use WP_REST_Request;

/**
 * UpdateUtils class.
 *
 * @internal This class is for internal use only and should not be used by external code.
 */
final class UpdateUtils {
	/**
	 * Update customer from request data.
	 *
	 * @param WC_Customer     $customer Customer object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating Whether creating a new customer. Unused parameter.
	 * @return void
	 * @throws WC_REST_Exception If there's an error updating the customer.
	 */
	public function update_customer_from_request( WC_Customer $customer, WP_REST_Request $request, bool $creating = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Customer email.
		if ( isset( $request['email'] ) ) {
			$customer->set_email( sanitize_email( $request['email'] ) );
		}

		// Customer password.
		if ( isset( $request['password'] ) ) {
			$customer->set_password( $request['password'] );
		}

		// Customer first name.
		if ( isset( $request['first_name'] ) ) {
			$customer->set_first_name( wc_clean( $request['first_name'] ) );
		}

		// Customer last name.
		if ( isset( $request['last_name'] ) ) {
			$customer->set_last_name( wc_clean( $request['last_name'] ) );
		}

		// Customer billing address.
		if ( isset( $request['billing'] ) && is_array( $request['billing'] ) ) {
			$this->update_customer_address( $customer, $request['billing'], 'billing' );
		}

		// Customer shipping address.
		if ( isset( $request['shipping'] ) && is_array( $request['shipping'] ) ) {
			$this->update_customer_address( $customer, $request['shipping'], 'shipping' );
		}

		// Save the customer.
		$customer->save();

		// Additional fields for user data.
		$user_data = get_userdata( $customer->get_id() );
		if ( $user_data ) {
			$this->update_additional_fields_for_object( $user_data, $request );

			// Ensure user is a member of the blog and has customer role.
			if ( ! is_user_member_of_blog( $user_data->ID ) ) {
				$user_data->add_role( 'customer' );
			}
		}
	}

	/**
	 * Update customer address fields.
	 *
	 * @param WC_Customer $customer Customer object.
	 * @param array       $address  Address data.
	 * @param string      $type     Address type (billing or shipping).
	 * @return void
	 */
	private function update_customer_address( WC_Customer $customer, array $address, string $type ): void {
		$address = wc_clean( $address );

		$address_fields = array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'email',
			'phone',
		);

		foreach ( $address_fields as $field ) {
			if ( isset( $address[ $field ] ) && is_callable( array( $customer, "set_{$type}_{$field}" ) ) ) {
				$value = ( 'email' === $field ) ? sanitize_email( $address[ $field ] ) : $address[ $field ];
				$customer->{"set_{$type}_{$field}"}( $value );
			}
		}
	}

	/**
	 * Update additional fields for object.
	 *
	 * @param mixed           $item    Object to update.
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 * @throws WC_REST_Exception If there's an error updating additional fields.
	 */
	private function update_additional_fields_for_object( $item, WP_REST_Request $request ): void {
		$additional_fields = $this->get_additional_fields();

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['update_callback'] || ! is_callable( $field_options['update_callback'] ) ) {
				continue;
			}

			if ( ! isset( $request[ $field_name ] ) ) {
				continue;
			}

			$result = call_user_func( $field_options['update_callback'], $request[ $field_name ], $item, $field_name, $request );

			if ( is_wp_error( $result ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_update', esc_html( $result->get_error_message() ), 400 );
			}
		}
	}

	/**
	 * Get additional fields for this object.
	 *
	 * @return array
	 */
	private function get_additional_fields(): array {
		$fields = array();

		/**
		 * Filter additional fields for objects of this type.
		 *
		 * @param array  $fields Additional fields registered for the object type.
		 * @param string $object_type Object type.
		 * @since 10.2.0
		 */
		$fields = apply_filters( 'rest_additional_fields', $fields, 'user' );

		/**
		 * Filter additional fields for objects of this type.
		 *
		 * @param array  $fields Additional fields registered for the object type.
		 * @param string $object_type Object type.
		 * @since 10.2.0
		 */
		$fields = apply_filters( "rest_{$this->get_object_type()}_additional_fields", $fields, 'user' );

		return $fields;
	}

	/**
	 * Get object type.
	 *
	 * @return string
	 */
	private function get_object_type(): string {
		return 'user';
	}
}
