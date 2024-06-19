<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Utilities\SanitizationUtils;
use Automattic\WooCommerce\StoreApi\Utilities\ValidationUtils;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\Blocks\Package;

/**
 * AddressSchema class.
 *
 * Provides a generic address schema for composition in other schemas.
 */
abstract class AbstractAddressSchema extends AbstractSchema {

	/**
	 * Additional fields controller.
	 *
	 * @var CheckoutFields
	 */
	protected $additional_fields_controller;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend ExtendSchema instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$this->additional_fields_controller = Package::container()->get( CheckoutFields::class );
	}
	/**
	 * Term properties.
	 *
	 * @internal Note that required properties don't require values, just that they are included in the request.
	 * @return array
	 */
	public function get_properties() {
		return array_merge(
			[
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
				'phone'      => [
					'description' => __( 'Phone', 'woocommerce' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'required'    => true,
				],
			],
			$this->get_additional_address_fields_schema(),
		);
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
		$validation_util   = new ValidationUtils();
		$sanitization_util = new SanitizationUtils();
		$address           = (array) $address;
		$schema            = $this->get_properties();
		// omit all keys from address that are not in the schema. This should account for email.
		$address = array_intersect_key( $address, $schema );
		$address = array_reduce(
			array_keys( $address ),
			function ( $carry, $key ) use ( $address, $validation_util, $schema ) {
				switch ( $key ) {
					case 'country':
						$carry[ $key ] = wc_strtoupper( sanitize_text_field( wp_unslash( $address[ $key ] ) ) );
						break;
					case 'state':
						$carry[ $key ] = $validation_util->format_state( sanitize_text_field( wp_unslash( $address[ $key ] ) ), $address['country'] );
						break;
					case 'postcode':
						$carry[ $key ] = $address['postcode'] ? wc_format_postcode( sanitize_text_field( wp_unslash( $address['postcode'] ) ), $address['country'] ) : '';
						break;
					default:
						$carry[ $key ] = rest_sanitize_value_from_schema( wp_unslash( $address[ $key ] ), $schema[ $key ], $key );
						break;
				}
				if ( $this->additional_fields_controller->is_field( $key ) ) {
					$carry[ $key ] = $this->additional_fields_controller->sanitize_field( $key, $carry[ $key ] );
				}
				return $carry;
			},
			[]
		);

		return $sanitization_util->wp_kses_array( $address );
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
		$errors          = new \WP_Error();
		$address         = (array) $address;
		$validation_util = new ValidationUtils();
		$schema          = $this->get_properties();
		// omit all keys from address that are not in the schema. This should account for email.
		$address = array_intersect_key( $address, $schema );

		// The flow is Validate -> Sanitize -> Re-Validate
		// First validation step is to ensure fields match their schema, then we sanitize to put them in the
		// correct format, and finally the second validation step is to ensure the correctly-formatted values
		// match what we expect (postcode etc.).
		foreach ( $address as $key => $value ) {
			if ( is_wp_error( rest_validate_value_from_schema( $value, $schema[ $key ], $key ) ) ) {
				$errors->add(
					'invalid_' . $key,
					sprintf(
						/* translators: %s: field name */
						__( 'Invalid %s provided.', 'woocommerce' ),
						$key
					)
				);
			}
		}

		// This condition will be true if any validation errors were encountered, e.g. wrong type supplied or invalid
		// option in enum fields.
		if ( $errors->has_errors() ) {
			return $errors;
		}

		$address = $this->sanitize_callback( $address, $request, $param );

		if ( ! empty( $address['country'] ) && ! in_array( $address['country'], array_keys( wc()->countries->get_countries() ), true ) ) {
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

		if ( ! empty( $address['state'] ) && ! $validation_util->validate_state( $address['state'], $address['country'] ) ) {
			$errors->add(
				'invalid_state',
				sprintf(
					/* translators: %1$s given state, %2$s valid states */
					__( 'The provided state (%1$s) is not valid. Must be one of: %2$s', 'woocommerce' ),
					esc_html( $address['state'] ),
					implode( ', ', array_keys( $validation_util->get_states_for_country( $address['country'] ) ) )
				)
			);
		}

		if ( ! empty( $address['postcode'] ) && ! \WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
			$errors->add(
				'invalid_postcode',
				__( 'The provided postcode / ZIP is not valid', 'woocommerce' )
			);
		}

		if ( ! empty( $address['phone'] ) && ! \WC_Validation::is_phone( $address['phone'] ) ) {
			$errors->add(
				'invalid_phone',
				__( 'The provided phone number is not valid', 'woocommerce' )
			);
		}

		// Get additional field keys here as we need to know if they are present in the address for validation.
		$additional_keys = array_keys( $this->get_additional_address_fields_schema() );

		foreach ( array_keys( $address ) as $key ) {

			// Skip email here it will be validated in BillingAddressSchema.
			if ( 'email' === $key ) {
				continue;
			}

			// Only run specific validation on properties that are defined in the schema and present in the address.
			// This is for partial address pushes when only part of a customer address is sent.
			// Full schema address validation still happens later, so empty, required values are disallowed.
			if ( empty( $schema[ $key ] ) || empty( $address[ $key ] ) ) {
				continue;
			}

			$result = rest_validate_value_from_schema( $address[ $key ], $schema[ $key ], $key );

			// Check if a field is in the list of additional fields then validate the value against the custom validation rules defined for it.
			// Skip additional validation if the schema validation failed.
			if ( true === $result && in_array( $key, $additional_keys, true ) ) {
				$result = $this->additional_fields_controller->validate_field( $key, $address[ $key ] );
			}

			if ( is_wp_error( $result ) && $result->has_errors() ) {
				$errors->merge_from( $result );
			}
		}

		$result = $this->additional_fields_controller->validate_fields_for_location( $address, 'address', 'billing_address' === $this->title ? 'billing' : 'shipping' );

		if ( is_wp_error( $result ) && $result->has_errors() ) {
			$errors->merge_from( $result );
		}

		return $errors->has_errors( $errors ) ? $errors : true;
	}

	/**
	 * Get additional address fields schema.
	 *
	 * @return array
	 */
	protected function get_additional_address_fields_schema() {
		$additional_fields_keys = $this->additional_fields_controller->get_address_fields_keys();

		$fields = $this->additional_fields_controller->get_additional_fields();

		$address_fields = array_filter(
			$fields,
			function ( $key ) use ( $additional_fields_keys ) {
				return in_array( $key, $additional_fields_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		$schema = [];
		foreach ( $address_fields as $key => $field ) {
			$field_schema = [
				'description' => $field['label'],
				'type'        => 'string',
				'context'     => [ 'view', 'edit' ],
				'required'    => $field['required'],
			];

			if ( 'select' === $field['type'] ) {
				$field_schema['enum'] = array_map(
					function ( $option ) {
						return $option['value'];
					},
					$field['options']
				);
			}

			if ( 'checkbox' === $field['type'] ) {
				$field_schema['type'] = 'boolean';
			}

			$schema[ $key ] = $field_schema;
		}
		return $schema;
	}
}
