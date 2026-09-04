<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

/**
 * The "select" additional checkout field type.
 */
class SelectFieldType extends AbstractFieldType {

	/**
	 * Processes the options for a select field and returns the new field_options array.
	 *
	 * @param array $field_data The field data array to be updated.
	 * @param array $options    The options supplied during field registration.
	 * @return array|false The updated $field_data array, or false if an error was encountered.
	 */
	protected function process_type_options( array $field_data, array $options ) {
		$id = $options['id'];

		if ( empty( $options['options'] ) || ! is_array( $options['options'] ) ) {
			return $this->registration_error( $id, 'Fields of type "select" must have an array of "options".', '8.6.0' );
		}

		$cleaned_options = [];

		foreach ( $options['options'] as $option ) {
			if ( ! isset( $option['value'], $option['label'] ) ) {
				return $this->registration_error( $id, 'Fields of type "select" must have an array of "options" and each option must contain a "value" and "label" member.', '8.6.0' );
			}

			$value = sanitize_text_field( $option['value'] );

			if ( isset( $cleaned_options[ $value ] ) ) {
				$this->doing_it_wrong( sprintf( 'Duplicate key found when registering field with id: "%s". The value in each option of "select" fields must be unique. Duplicate value "%s" found. The duplicate key will be removed.', $id, $value ), '8.6.0' );
				continue;
			}

			$cleaned_options[ $value ] = [
				'value' => $value,
				'label' => sanitize_text_field( $option['label'] ),
			];
		}

		$field_data['options'] = array_values( $cleaned_options );

		if ( isset( $field_data['placeholder'] ) ) {
			$field_data['placeholder'] = sanitize_text_field( $field_data['placeholder'] );
		}

		return $field_data;
	}

	/**
	 * Formats a stored option value as its registered label for display.
	 *
	 * @param mixed $value The stored value.
	 * @param array $field The field.
	 * @return mixed The option label, or the value unchanged if it is not a registered option.
	 */
	public function format_value( $value, array $field ) {
		$options = array_column( $field['options'], 'label', 'value' );

		return $options[ $value ] ?? $value;
	}

	/**
	 * Maps the registered options to the value => label format woocommerce_form_field() expects.
	 *
	 * @param array $form_field The woocommerce_form_field() arguments built from the field.
	 * @return array The updated arguments.
	 */
	public function prepare_form_field( array $form_field ): array {
		$form_field['options'] = array_column( $form_field['options'], 'label', 'value' );

		if ( ! empty( $form_field['placeholder'] ) && ! array_key_exists( '', $form_field['options'] ) ) {
			$form_field['options'] = array( '' => $form_field['placeholder'] ) + $form_field['options'];
		}

		return $form_field;
	}
}
