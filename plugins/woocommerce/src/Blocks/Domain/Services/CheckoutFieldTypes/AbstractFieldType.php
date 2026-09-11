<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation;
use WP_Error;

/**
 * Base class for additional checkout field types.
 *
 * Provides passthrough defaults so each field type only overrides the behavior it needs. New
 * type-level behavior should be added here with a default implementation so existing subclasses
 * keep working.
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter -- The passthrough defaults keep the
 * signature subclasses override.
 */
abstract class AbstractFieldType {

	/**
	 * Validates the options that apply to every field type: callbacks, hidden state, and rule schemas.
	 *
	 * Subclasses adding checks should call this parent method first.
	 *
	 * @param array $options The options supplied during field registration.
	 * @return bool False if an error should prevent registration, true otherwise.
	 */
	public function validate_options( array $options ): bool {
		$id = $options['id'];

		if ( empty( $options['label'] ) ) {
			return $this->registration_error( $id, 'The field label is required.', '8.6.0' );
		}

		foreach ( array( 'sanitize_callback', 'validate_callback' ) as $callback ) {
			if ( ! empty( $options[ $callback ] ) && ! is_callable( $options[ $callback ] ) ) {
				return $this->registration_error( $id, sprintf( 'The %s must be a valid callback.', $callback ), '8.6.0' );
			}
		}

		if ( ! empty( $options['hidden'] ) && true === $options['hidden'] ) {
			// Not an error: the field is still registered, just as a visible one.
			$this->doing_it_wrong( sprintf( 'Registering a field with hidden set to true is not supported. The field "%s" will be registered as visible.', $id ), '8.6.0' );
		}

		foreach ( array( 'required', 'hidden', 'validation' ) as $rule_field ) {
			if ( empty( $options[ $rule_field ] ) || ( 'validation' !== $rule_field && is_bool( $options[ $rule_field ] ) ) ) {
				continue;
			}

			$valid = Validation::is_valid_schema( $options[ $rule_field ] );

			if ( is_wp_error( $valid ) ) {
				return $this->registration_error( $id, $rule_field . ': ' . $valid->get_error_message(), '8.6.0' );
			}
		}

		return true;
	}

	/**
	 * Processes and validates the options supplied during field registration.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error should prevent registration.
	 */
	final public function process_options( array $field_data, array $options ) {
		$field_data['attributes'] = $this->process_attributes( $field_data['id'], $field_data['attributes'] );

		return $this->process_type_options( $field_data, $options );
	}

	/**
	 * Processes the type-specific options supplied during field registration.
	 *
	 * @param array $field_data The field data array, with the options common to every type already applied.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error should prevent registration.
	 */
	protected function process_type_options( array $field_data, array $options ) {
		return $field_data;
	}

	/**
	 * Processes the attributes supplied during field registration.
	 *
	 * Invalid attributes are dropped with a warning rather than preventing registration.
	 *
	 * @param string $id         The field ID.
	 * @param mixed  $attributes The attributes supplied during field registration.
	 * @return array The processed attributes.
	 */
	protected function process_attributes( $id, $attributes ): array {
		if ( empty( $attributes ) ) {
			return [];
		}

		if ( ! is_array( $attributes ) ) {
			$this->doing_it_wrong( sprintf( 'An invalid attributes value was supplied when registering field with id: "%s". Attributes must be a non-empty array.', $id ), '8.6.0' );
			return [];
		}

		// These are formatted in camelCase because React components expect them that way.
		$allowed_attributes = [ 'maxLength', 'readOnly', 'pattern', 'autocomplete', 'autocapitalize', 'title' ];

		$valid_attributes = array_filter(
			$attributes,
			function ( $_, $key ) use ( $allowed_attributes ) {
				return in_array( $key, $allowed_attributes, true ) || strpos( $key, 'aria-' ) === 0 || strpos( $key, 'data-' ) === 0;
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( count( $attributes ) !== count( $valid_attributes ) ) {
			$invalid_attributes = array_keys( array_diff_key( $attributes, $valid_attributes ) );
			$this->doing_it_wrong( sprintf( 'Invalid attribute found when registering field with id: "%s". Attributes: %s are not allowed.', $id, implode( ', ', $invalid_attributes ) ), '8.6.0' );
		}

		return array_map( 'esc_attr', $valid_attributes );
	}

	/**
	 * Sanitizes a submitted value for this field type.
	 *
	 * @param mixed $value The submitted value.
	 * @param array $field The field.
	 * @return mixed The sanitized value.
	 */
	public function sanitize( $value, array $field ) {
		return $value;
	}

	/**
	 * The validate_callback a field gets when it does not declare its own: rejects empty required fields.
	 *
	 * Unlike validate(), a field's validate_callback can be replaced at registration, so nothing here is
	 * mandatory for the type.
	 *
	 * @param mixed $value The submitted value.
	 * @param array $field The field.
	 * @return WP_Error|void An error if the value is invalid.
	 */
	public function default_validate( $value, $field ) {
		if ( true === $field['required'] && empty( $value ) ) {
			return new WP_Error(
				'woocommerce_required_checkout_field',
				sprintf(
					// translators: %s is field key.
					__( 'The field %s is required.', 'woocommerce' ),
					$field['id']
				)
			);
		}
	}

	/**
	 * Validates a submitted value against the constraints of this field type.
	 *
	 * @param mixed $value The submitted value.
	 * @param array $field The field.
	 * @return \WP_Error|null Error if the value is not valid for the field type, null otherwise.
	 */
	public function validate( $value, array $field ) {
		return null;
	}

	/**
	 * Formats a stored value for display based on the field type.
	 *
	 * @param mixed $value The stored value.
	 * @param array $field The field.
	 * @return mixed The formatted value.
	 */
	public function format_value( $value, array $field ) {
		return $value;
	}

	/**
	 * Applies type-specific arguments to a field before it is rendered with woocommerce_form_field().
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		return $form_field;
	}

	/**
	 * Reports a field registration error and prevents the field from being registered.
	 *
	 * @param string $id      The ID of the field being registered.
	 * @param string $reason  The reason the field cannot be registered.
	 * @param string $version The version the misuse was introduced in.
	 * @return false
	 */
	protected function registration_error( string $id, string $reason, string $version ): bool {
		return $this->doing_it_wrong( sprintf( 'Unable to register field with id: "%s". %s', $id, $reason ), $version );
	}

	/**
	 * Reports a field registration misuse that does not prevent the field from being registered.
	 *
	 * @param string $message The message describing the misuse.
	 * @param string $version The version the misuse was introduced in.
	 * @return false
	 */
	protected function doing_it_wrong( string $message, string $version ): bool {
		_doing_it_wrong( 'woocommerce_register_additional_checkout_field', esc_html( $message ), esc_html( $version ) );
		return false;
	}
}
