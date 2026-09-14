<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

/**
 * The "checkbox" additional checkout field type.
 */
class CheckboxFieldType extends AbstractFieldType {

	/**
	 * Processes the options for a checkbox field and returns the new field_options array.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error was encountered.
	 */
	protected function process_type_options( array $field_data, array $options ) {
		$id                     = $options['id'];
		$field_data['required'] = $options['required'] ?? false;

		if ( false === $field_data['required'] && ! empty( $options['error_message'] ) ) {
			$this->doing_it_wrong( sprintf( 'Passing an error message to a non-required checkbox "%s" will have no effect. The error message has been removed from the field.', $id ), '9.8.0' );
			unset( $field_data['error_message'] );
		}

		if ( isset( $options['error_message'] ) && ! is_string( $options['error_message'] ) ) {
			$this->doing_it_wrong( sprintf( 'The error_message property for field with id: "%s" must be a string, you passed %s. A default message will be shown.', $id, gettype( $options['error_message'] ) ), '9.8.0' );
			unset( $field_data['error_message'] );
		}

		// The client expects the error message in camelCase.
		if ( isset( $field_data['error_message'] ) ) {
			$field_data['errorMessage'] = $field_data['error_message'];
			unset( $field_data['error_message'] );
		}

		return $field_data;
	}

	/**
	 * Formats a stored checkbox value as Yes/No for display.
	 *
	 * @param mixed $value The stored value.
	 * @param array $field The field.
	 * @return string
	 */
	public function format_value( $value, array $field ) {
		return $value ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' );
	}

	/**
	 * Sets the checked and unchecked values woocommerce_form_field() should submit.
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		$form_field['checked_value']   = '1';
		$form_field['unchecked_value'] = '0';

		return $form_field;
	}
}
