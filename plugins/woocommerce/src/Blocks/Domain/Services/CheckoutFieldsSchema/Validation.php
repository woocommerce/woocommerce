<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Opis\JsonSchema\{
	Helper,
	Validator
};
use WP_Error;

/**
 * Service class validating checkout field schema.
 */
class Validation {
	/**
	 * Meta schema.
	 *
	 * @var string
	 */
	private static $meta_schema_json = '';

	/**
	 * Get the field schema with context.
	 *
	 * @param string $field_id The field ID.
	 * @param array  $field_schema The field schema.
	 * @param string $context The context.
	 * @return array
	 */
	public static function get_field_schema_with_context( $field_id, $field_schema, $context ) {
		$primary_key   = 'checkout';
		$secondary_key = 'additional_fields';
		switch ( $context ) {
			case 'billing_address':
			case 'shipping_address':
				$primary_key   = 'customer';
				$secondary_key = $context;
				break;
			case 'contact':
				$primary_key   = 'customer';
				$secondary_key = 'additional_fields';
				break;
		}
		return [
			$primary_key => [
				'properties' => [
					$secondary_key => [
						'properties' => [
							$field_id => $field_schema,
						],
					],
				],
			],
		];
	}

	/**
	 * Validate the field rules.
	 *
	 * @param DocumentObject $document_object The document object to validate.
	 * @param array          $rules The rules to validate against.
	 * @return bool|WP_Error
	 */
	public static function validate_document_object( DocumentObject $document_object, $rules ) {
		try {
			$validator = new Validator();
			$result    = $validator->validate(
				Helper::toJSON( $document_object->get_data() ),
				Helper::toJSON(
					[
						'$schema'    => 'http://json-schema.org/draft-07/schema#',
						'type'       => 'object',
						'properties' => $rules,
					]
				)
			);

			if ( ! $result->hasError() ) {
				return true;
			}
		} catch ( \Exception $e ) {
			return new WP_Error( 'woocommerce_rest_checkout_validation_failed', __( 'Validation failed.', 'woocommerce' ) );
		}

		// Return generic error message.
		return new WP_Error( 'woocommerce_rest_checkout_invalid_field', __( 'Invalid field.', 'woocommerce' ) );
	}

	/**
	 * Check if the fields have defined schema.
	 *
	 * @param array $fields The fields.
	 * @return bool
	 */
	public static function has_field_schema( $fields ) {
		$return = false;

		foreach ( $fields as $field ) {
			if (
				( ! empty( $field['validation'] ) && is_array( $field['validation'] ) ) ||
				( ! empty( $field['required'] ) && is_array( $field['required'] ) ) ||
				( ! empty( $field['hidden'] ) && is_array( $field['hidden'] ) )
			) {
				$return = true;
				break;
			}
		}

		return $return;
	}

	/**
	 * Validate meta schema for field rules.
	 *
	 * @param mixed $rules The rules to validate.
	 * @return bool|WP_Error True if the field options are valid, a WP_Error otherwise.
	 */
	public static function is_valid_schema( $rules ) {
		if ( ! is_array( $rules ) ) {
			return new WP_Error( 'woocommerce_rest_checkout_invalid_field_schema', 'Rules must be defined as an array.' );
		}

		if ( empty( $rules ) ) {
			return true;
		}

		if ( empty( self::$meta_schema_json ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			self::$meta_schema_json = file_get_contents( __DIR__ . '/json-schema-draft-07.json' );
		}

		$validator = new Validator();
		$result    = $validator->validate(
			Helper::toJSON(
				[
					'$schema'    => 'http://json-schema.org/draft-07/schema#',
					'type'       => 'object',
					'properties' => [
						'test' => $rules,
					],
					'required'   => [ 'test' ],
				]
			),
			self::$meta_schema_json
		);

		if ( $result->hasError() ) {
			return new WP_Error( 'woocommerce_rest_checkout_invalid_field_schema', esc_html( (string) $result->error() ) );
		}

		return true;
	}
}
