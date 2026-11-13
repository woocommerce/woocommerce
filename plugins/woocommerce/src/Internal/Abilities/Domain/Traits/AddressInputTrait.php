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

		$sent_fields = array_keys( (array) $address );
		$sent        = static fn( string ...$fields ): bool => empty( array_diff( $fields, $sent_fields ) );

		if ( '' !== $country && ! array_key_exists( $country, WC()->countries->get_countries() ) ) {
			$refused->merge_from(
				self::address_error(
					$resource_name,
					$type,
					'country',
					$sent( 'country' )
						/* translators: %s: country sent by the client. */
						? sprintf( __( '"%s" is not a valid country. Send an ISO 3166-1 alpha-2 country code, such as PT.', 'woocommerce' ), $country )
						: __( 'The saved country is not valid. Send an ISO 3166-1 alpha-2 country code, such as PT.', 'woocommerce' )
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
						self::get_state_message( $sent( 'state' ), $sent( 'country' ), $state, $country, array_keys( $states ) )
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
							self::get_postcode_message( $sent( 'postcode' ), $sent( 'country' ), $postcode, $country )
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
	 * Explain a state that does not belong to the country, quoting only values the client sent.
	 *
	 * @param bool               $state_sent   Whether the client sent the state.
	 * @param bool               $country_sent Whether the client sent the country.
	 * @param string             $state        State code.
	 * @param string             $country      Country code.
	 * @param array<int, string> $state_codes  State codes of the country.
	 * @return string
	 */
	private static function get_state_message( bool $state_sent, bool $country_sent, string $state, string $country, array $state_codes ): string {
		if ( $state_sent && $country_sent ) {
			/* translators: 1: state sent by the client, 2: country code sent by the client, 3: comma-separated state codes. */
			return sprintf( __( '"%1$s" is not a state of %2$s. Send the state with one of these codes: %3$s.', 'woocommerce' ), $state, $country, implode( ', ', $state_codes ) );
		}
		if ( $country_sent ) {
			/* translators: 1: country code sent by the client, 2: comma-separated state codes. */
			return sprintf( __( 'The saved state is not a state of %1$s. Send the state with one of these codes: %2$s.', 'woocommerce' ), $country, implode( ', ', $state_codes ) );
		}
		if ( $state_sent ) {
			/* translators: %s: state sent by the client. */
			return sprintf( __( '"%s" is not a state of the saved country. Send the country and the state together.', 'woocommerce' ), $state );
		}
		return __( 'The saved state does not belong to the saved country. Send the country and the state together.', 'woocommerce' );
	}

	/**
	 * Explain a postcode that does not match the country, quoting only values the client sent.
	 *
	 * @param bool   $postcode_sent Whether the client sent the postcode.
	 * @param bool   $country_sent  Whether the client sent the country.
	 * @param string $postcode      Postcode.
	 * @param string $country       Country code.
	 * @return string
	 */
	private static function get_postcode_message( bool $postcode_sent, bool $country_sent, string $postcode, string $country ): string {
		if ( $postcode_sent && $country_sent ) {
			/* translators: 1: postcode sent by the client, 2: country code sent by the client. */
			return sprintf( __( '"%1$s" is not a valid postcode for %2$s. Send a postcode in the format of that country.', 'woocommerce' ), $postcode, $country );
		}
		if ( $country_sent ) {
			/* translators: %s: country code sent by the client. */
			return sprintf( __( 'The saved postcode is not valid for %s. Send a postcode in the format of that country.', 'woocommerce' ), $country );
		}
		if ( $postcode_sent ) {
			/* translators: %s: postcode sent by the client. */
			return sprintf( __( '"%s" is not a valid postcode for the saved country. Send a postcode in the format of that country.', 'woocommerce' ), $postcode );
		}
		return __( 'The saved postcode does not match the saved country. Send the country and the postcode together.', 'woocommerce' );
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
