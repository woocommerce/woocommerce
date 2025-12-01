<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator;

use function wp_json_encode;
use function rest_get_allowed_schema_keywords;

/**
 * Represents abastract schema.
 */
abstract class Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array();

	/**
	 * Sets the schema as nullable.
	 *
	 * @return static
	 */
	public function nullable() {
		$type = $this->schema['type'] ?? array( 'null' );
		return $this->update_schema_property( 'type', is_array( $type ) ? $type : array( $type, 'null' ) );
	}

	/**
	 * Sets the schema as non-nullable.
	 *
	 * @return static
	 */
	public function non_nullable() {
		$type = $this->schema['type'] ?? null;
		return null === $type
		? $this->unset_schema_property( 'type' )
		: $this->update_schema_property( 'type', is_array( $type ) ? $type[0] : $type );
	}

	/**
	 * Sets the schema as required.
	 *
	 * @return static
	 */
	public function required() {
		return $this->update_schema_property( 'required', true );
	}

	/**
	 * Unsets the required property.
	 *
	 * @return static
	 */
	public function optional() {
		return $this->unset_schema_property( 'required' );
	}

	/**
	 * Set the title of the schema.
	 *
	 * @param string $title Title.
	 * @return static
	 */
	public function title( string $title ) {
		return $this->update_schema_property( 'title', $title );
	}

	/**
	 * Set the description of the schema.
	 *
	 * @param string $description Description.
	 * @return static
	 */
	public function description( string $description ) {
		return $this->update_schema_property( 'description', $description );
	}

	/**
	 * Set the default value.
	 *
	 * @param mixed $default_value Default value.
	 * @return static
	 */
	public function default( $default_value ) {
		return $this->update_schema_property( 'default', $default_value );
	}

	/**
	 * Set the field name and value.
	 *
	 * @param string $name Name of the field.
	 * @param mixed  $value Value of the field.
	 * @return static
	 * @throws \Exception When the field name is reserved.
	 */
	public function field( string $name, $value ) {
		if ( in_array( $name, $this->get_reserved_keywords(), true ) ) {
			throw new \Exception( \esc_html( "Field name '$name' is reserved" ) );
		}
		return $this->update_schema_property( $name, $value );
	}

	/**
	 * Returns the schema as an array.
	 */
	public function to_array(): array {
		return $this->schema;
	}

	/**
	 * Returns the schema as a JSON string.
	 *
	 * @throws \Exception When the schema cannot be converted to JSON.
	 */
	public function to_string(): string {
		$json  = wp_json_encode( $this->schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION );
		$error = json_last_error();
		if ( $error || false === $json ) {
			throw new \Exception( \esc_html( json_last_error_msg() ), 0 );
		}
		return $json;
	}

	/**
	 * Updates the schema property.
	 *
	 * @param string $name Property name.
	 * @param mixed  $value Property value.
	 * @return static
	 */
	protected function update_schema_property( string $name, $value ) {
		$clone                  = clone $this;
		$clone->schema[ $name ] = $value;
		return $clone;
	}

	/**
	 * Unsets the schema property.
	 *
	 * @param string $name Property name.
	 * @return static
	 */
	protected function unset_schema_property( string $name ) {
		$clone = clone $this;
		unset( $clone->schema[ $name ] );
		return $clone;
	}

	/**
	 * Returns reserved keywords.
	 *
	 * @return string[]
	 */
	protected function get_reserved_keywords(): array {
		return rest_get_allowed_schema_keywords();
	}

	/**
	 * Validates the regular expression pattern.
	 *
	 * @param string $pattern Regular expression pattern.
	 * @throws \Exception When the pattern is invalid.
	 */
	protected function validate_pattern( string $pattern ): void {
		$escaped = str_replace( '#', '\\#', $pattern );
		$regex   = "#$escaped#u";
		if ( @preg_match( $regex, '' ) === false ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			throw new \Exception( \esc_html( "Invalid regular expression '$regex'" ) );
		}
	}
}
