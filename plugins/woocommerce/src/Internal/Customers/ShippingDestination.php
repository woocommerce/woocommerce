<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

use WC_Customer;
use WC_Data;

/**
 * Applies the "Shipping destination" setting to a customer's shipping address.
 *
 * @since 11.4.0
 */
final class ShippingDestination {
	/**
	 * Address fields that make up the location an address ships to.
	 */
	public const LOCATION_FIELDS = array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' );

	/**
	 * Address fields that say who receives the parcel. Not every checkout posts all of them.
	 */
	public const RECIPIENT_FIELDS = array( 'first_name', 'last_name', 'company', 'phone' );

	/**
	 * Checks whether the "Shipping destination" setting defaults to, or forces, the customer's billing address.
	 *
	 * @return bool
	 */
	public function defaults_to_billing(): bool {
		return 'shipping' !== get_option( 'woocommerce_ship_to_destination', 'billing' );
	}

	/**
	 * Reads an order or customer address, without the getter filters.
	 *
	 * @param WC_Data $data_object  Order or customer.
	 * @param string  $address_type 'billing' or 'shipping'.
	 * @return array<string, string> Field values keyed by field name without the address type prefix.
	 */
	public function get_address( WC_Data $data_object, string $address_type ): array {
		$address = array();

		foreach ( array_merge( self::RECIPIENT_FIELDS, self::LOCATION_FIELDS ) as $field ) {
			$getter = "get_{$address_type}_{$field}";

			if ( is_callable( array( $data_object, $getter ) ) ) {
				$address[ $field ] = (string) $data_object->{$getter}( 'edit' );
			}
		}

		return $address;
	}

	/**
	 * Checks whether two addresses are at the same location. Surrounding spaces and postcode formatting are ignored.
	 *
	 * @param array $address       Address fields keyed by field name without the address type prefix.
	 * @param array $other_address Address fields to compare with, keyed the same way.
	 * @return bool
	 */
	public function is_same_location( array $address, array $other_address ): bool {
		foreach ( self::LOCATION_FIELDS as $field ) {
			if ( $this->normalize( $field, $address[ $field ] ?? '' ) !== $this->normalize( $field, $other_address[ $field ] ?? '' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks whether two addresses are the same, recipient and any other fields, such as additional checkout fields, included.
	 *
	 * Name, company and phone are only compared when both addresses have them, since a checkout may not post all of them.
	 * Any other field missing from one address counts as empty.
	 *
	 * @param array $address       Address fields keyed by field name without the address type prefix.
	 * @param array $other_address Address fields to compare with, keyed the same way.
	 * @return bool
	 */
	public function is_same_address( array $address, array $other_address ): bool {
		foreach ( array_keys( $address + $other_address ) as $field ) {
			$field = (string) $field;

			if ( in_array( $field, self::RECIPIENT_FIELDS, true ) && ! isset( $address[ $field ], $other_address[ $field ] ) ) {
				continue;
			}

			if ( $this->normalize( $field, $address[ $field ] ?? '' ) !== $this->normalize( $field, $other_address[ $field ] ?? '' ) ) {
				return false;
			}
		}

		return $this->is_same_location( $address, $other_address );
	}

	/**
	 * Checks whether saving an order to the customer's account should leave the account's shipping address as it is.
	 *
	 * True when the setting defaults to billing, the order ships to its billing address, and the account has a shipping
	 * address saved at another location. Saving the order's shipping address would replace that saved address with the
	 * billing one. An order shipped to someone else at the billing location is saved, like any other shipping address.
	 *
	 * @param array       $billing  Order billing address, keyed by field name without the 'billing_' prefix.
	 * @param array       $shipping Order shipping address, keyed by field name without the 'shipping_' prefix.
	 * @param WC_Customer $customer The customer as saved to their account, before the order is saved to it.
	 * @return bool
	 */
	public function keeps_saved_shipping_address( array $billing, array $shipping, WC_Customer $customer ): bool {
		if ( ! $this->defaults_to_billing() || ! $this->is_same_address( $billing, $shipping ) ) {
			return false;
		}

		$saved_shipping = $this->get_address( $customer, 'shipping' );

		if ( '' === trim( $saved_shipping['address_1'] . $saved_shipping['city'] . $saved_shipping['postcode'] ) ) {
			return false;
		}

		return ! $this->is_same_location( $saved_shipping, $shipping );
	}

	/**
	 * Normalizes an address field value for comparison.
	 *
	 * @param string $field Field name without the address type prefix.
	 * @param mixed  $value Field value.
	 * @return string
	 */
	private function normalize( string $field, $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : (string) wp_json_encode( $value );

		return 'postcode' === $field ? wc_normalize_postcode( $value ) : $value;
	}
}
