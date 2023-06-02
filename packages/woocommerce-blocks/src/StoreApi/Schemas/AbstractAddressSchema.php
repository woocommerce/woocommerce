<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Blocks\RestApi\Routes;

/**
 * AddressSchema class.
 *
 * Provides a generic address schema for composition in other schemas.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 * @since 4.1.0
 */
abstract class AbstractAddressSchema extends AbstractSchema {
	/**
	 * Term properties.
	 *
	 * @internal Note that required properties don't require values, just that they are included in the request.
	 * @return array
	 */
	public function get_properties() {
		return [
			'first_name' => [
				'description' => __( 'First name', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'last_name'  => [
				'description' => __( 'Last name', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'company'    => [
				'description' => __( 'Company', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'address_1'  => [
				'description' => __( 'Address', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'address_2'  => [
				'description' => __( 'Apartment, suite, etc.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'city'       => [
				'description' => __( 'City', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'state'      => [
				'description' => __( 'State/County code, or name of the state, county, province, or district.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'postcode'   => [
				'description' => __( 'Postal code', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
			'country'    => [
				'description' => __( 'Country/Region code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => true,
			],
		];
	}

	/**
	 * Sanitize and format the given address object.
	 *
	 * @param array            $address Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @param string           $param The param being sanitized.
	 * @return array
	 */
	public function sanitize_callback( $address, $request, $param ) {
		$address               = array_merge( array_fill_keys( array_keys( $this->get_properties() ), '' ), (array) $address );
		$address['country']    = wc_strtoupper( wc_clean( wp_unslash( $address['country'] ) ) );
		$address['first_name'] = wc_clean( wp_unslash( $address['first_name'] ) );
		$address['last_name']  = wc_clean( wp_unslash( $address['last_name'] ) );
		$address['company']    = wc_clean( wp_unslash( $address['company'] ) );
		$address['address_1']  = wc_clean( wp_unslash( $address['address_1'] ) );
		$address['address_2']  = wc_clean( wp_unslash( $address['address_2'] ) );
		$address['city']       = wc_clean( wp_unslash( $address['city'] ) );
		$address['state']      = $this->format_state( wc_clean( wp_unslash( $address['state'] ) ), $address['country'] );
		$address['postcode']   = wc_format_postcode( wc_clean( wp_unslash( $address['postcode'] ) ), $address['country'] );
		return $address;
	}

	/**
	 * Format a state based on the country. If country has defined states, will return an upper case state code.
	 *
	 * @param string $state State name or code (sanitized).
	 * @param string $country Country code.
	 * @return string
	 */
	protected function format_state( $state, $country ) {
		$states = $country ? array_filter( (array) wc()->countries->get_states( $country ) ) : [];

		if ( count( $states ) ) {
			$state        = wc_strtoupper( $state );
			$state_values = array_map( 'wc_strtoupper', array_flip( array_map( 'wc_strtoupper', $states ) ) );

			if ( isset( $state_values[ $state ] ) ) {
				// Convert to state code if a state name was provided.
				return $state_values[ $state ];
			}
		}

		return $state;
	}

	/**
	 * Validate the given address object.
	 *
	 * @see rest_validate_value_from_schema
	 *
	 * @param array            $address Value being sanitized.
	 * @param \WP_REST_Request $request The Request.
	 * @param string           $param The param being sanitized.
	 * @return true|\WP_Error
	 */
	public function validate_callback( $address, $request, $param ) {
		$errors  = new \WP_Error();
		$address = $this->sanitize_callback( $address, $request, $param );

		if ( empty( $address['country'] ) ) {
			$errors->add(
				'missing_country',
				__( 'Country is required', 'woocommerce' )
			);
			return $errors;
		}

		if ( ! in_array( $address['country'], array_keys( wc()->countries->get_countries() ), true ) ) {
			$errors->add(
				'invalid_country',
				sprintf(
					/* translators: %s valid country codes */
					__( 'Invalid country code provided. Must be one of: %s', 'woocommerce' ),
					implode( ', ', array_keys( wc()->countries->get_countries() ) )
				)
			);
			return $errors;
		}

		$states = array_filter( array_keys( (array) wc()->countries->get_states( $address['country'] ) ) );

		if ( ! empty( $address['state'] ) && count( $states ) && ! in_array( $address['state'], $states, true ) ) {
			$errors->add(
				'invalid_state',
				sprintf(
					/* translators: %s valid states */
					__( 'The provided state is not valid. Must be one of: %s', 'woocommerce' ),
					implode( ', ', $states )
				)
			);
		}

		if ( ! empty( $address['postcode'] ) && ! \WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
			$errors->add(
				'invalid_postcode',
				__( 'The provided postcode / ZIP is not valid', 'woocommerce' )
			);
		}

		return $errors->has_errors( $errors ) ? $errors : true;
	}
}
