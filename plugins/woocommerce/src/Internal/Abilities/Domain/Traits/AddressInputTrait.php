<?php
/**
 * Address input trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Billing and shipping address input for abilities that write an order or a customer.
 */
trait AddressInputTrait {

	/**
	 * Address fields that the shipping address has. The billing address also has `email`.
	 *
	 * @return array<int, string>
	 */
	private static function get_address_fields(): array {
		return array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' );
	}

	/**
	 * Check the sent fields of an address against the object, then set the fields
	 * that pass. A bad country keeps the saved country, state and postcode, because
	 * the state and postcode checks depend on the country.
	 *
	 * @param \WC_Order|\WC_Customer $subject       Order or customer.
	 * @param string                 $resource_name Resource in the error codes: order or customer.
	 * @param string                 $type          Address type: billing or shipping.
	 * @param array                  $address       Address fields to change.
	 * @return null|\WP_Error
	 */
	private static function set_address( $subject, string $resource_name, string $type, array $address ): ?\WP_Error {
		$address = wc_clean( $address );
		$refused = new \WP_Error();
		if ( ! empty( $address['email'] ) && ! is_email( $address['email'] ) ) {
			$refused->merge_from(
				self::address_error(
					$resource_name,
					$type,
					'email',
					__( 'The billing email is not a valid email address. Send a valid address.', 'woocommerce' )
				)
			);
			unset( $address['email'] );
		}

		$country  = $address['country'] ?? $subject->{"get_{$type}_country"}();
		$state    = $address['state'] ?? $subject->{"get_{$type}_state"}();
		$postcode = $address['postcode'] ?? $subject->{"get_{$type}_postcode"}();

		if ( '' !== $country && ! array_key_exists( $country, WC()->countries->get_countries() ) ) {
			$refused->merge_from(
				self::address_error(
					$resource_name,
					$type,
					'country',
					/* translators: %s: country sent by the client. */
					sprintf( __( '"%s" is not a valid country. Send an ISO 3166-1 alpha-2 country code, such as PT.', 'woocommerce' ), $country )
				)
			);
			unset( $address['country'], $address['state'], $address['postcode'] );
		} else {
			$states = WC()->countries->get_states( $country );

			if ( '' !== $state && ! empty( $states ) && ! array_key_exists( $state, $states ) ) {
				$refused->merge_from(
					self::address_error(
						$resource_name,
						$type,
						'state',
						/* translators: 1: state sent by the client or already saved, 2: country code, 3: comma-separated state codes. */
						sprintf( __( '"%1$s" is not a state of %2$s. Send the state with one of these codes: %3$s.', 'woocommerce' ), $state, $country, implode( ', ', array_keys( $states ) ) )
					)
				);
				unset( $address['state'] );
			}

			if ( '' !== $postcode && '' !== $country ) {
				if ( ! \WC_Validation::is_postcode( $postcode, $country ) ) {
					$refused->merge_from(
						self::address_error(
							$resource_name,
							$type,
							'postcode',
							/* translators: 1: postcode sent by the client or already saved, 2: country code. */
							sprintf( __( '"%1$s" is not a valid postcode for %2$s. Send a postcode in the format of that country.', 'woocommerce' ), $postcode, $country )
						)
					);
					unset( $address['postcode'] );
				} elseif ( isset( $address['postcode'] ) ) {
					$address['postcode'] = wc_format_postcode( $postcode, $country );
				}
			}
		}

		foreach ( $address as $key => $value ) {
			$subject->{"set_{$type}_{$key}"}( $value );
		}

		return $refused->has_errors() ? $refused : null;
	}

	/**
	 * Build the error for an address field.
	 *
	 * @param string $resource_name Resource in the error code: order or customer.
	 * @param string $type          Address type: billing or shipping.
	 * @param string $field         Address field.
	 * @param string $message       What is wrong and what to send.
	 * @return \WP_Error
	 */
	private static function address_error( string $resource_name, string $type, string $field, string $message ): \WP_Error {
		return new \WP_Error( "woocommerce_ability_{$resource_name}_{$type}_{$field}_invalid", $message, array( 'status' => 400 ) );
	}

	/**
	 * Get the input schema of an address.
	 *
	 * @param array<int, string> $fields Address fields.
	 * @return array
	 */
	private static function get_address_input_schema( array $fields ): array {
		return array(
			'type'                 => 'object',
			'description'          => __( 'Countries use ISO 3166-1 alpha-2 codes, and states use WooCommerce state codes.', 'woocommerce' ),
			'properties'           => array_fill_keys( $fields, array( 'type' => 'string' ) ),
			'additionalProperties' => false,
			'minProperties'        => 1,
		);
	}
}
