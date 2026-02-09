<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator\Schema;

use Automattic\WooCommerce\EmailEditor\Validator\Schema;

/**
 * Represents a schema for an object.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#objects
 */
class Object_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'object',
	);

	/**
	 * Set the required properties of the object.
	 *
	 * @param array<string, Schema> $properties Required properties.
	 */
	public function properties( array $properties ): self {
		return $this->update_schema_property(
			'properties',
			array_map(
				function ( Schema $property ) {
					return $property->to_array();
				},
				$properties
			)
		);
	}

	/**
	 * Set the required properties of the object.
	 *
	 * @param Schema $schema Schema of the additional properties.
	 */
	public function additionalProperties( Schema $schema ): self {
		return $this->update_schema_property( 'additionalProperties', $schema->to_array() );
	}

	/**
	 * Disables additional properties.
	 */
	public function disableAdditionalProperties(): self {
		return $this->update_schema_property( 'additionalProperties', false );
	}

	/**
	 * Keys of $properties are regular expressions without leading/trailing delimiters.
	 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#patternproperties
	 *
	 * @param array<string, Schema> $properties Regular expressions and their schemas.
	 */
	public function patternProperties( array $properties ): self {
		$pattern_properties = array();
		foreach ( $properties as $key => $value ) {
			$this->validate_pattern( $key );
			$pattern_properties[ $key ] = $value->to_array();
		}
		return $this->update_schema_property( 'patternProperties', $pattern_properties );
	}

	/**
	 * Sets the minimum number of properties in the object.
	 *
	 * @param int $value Minimum number of properties in the object.
	 */
	public function minProperties( int $value ): self {
		return $this->update_schema_property( 'minProperties', $value );
	}

	/**
	 * Sets the maximum number of properties in the object.
	 *
	 * @param int $value Maximum number of properties in the object.
	 */
	public function maxProperties( int $value ): self {
		return $this->update_schema_property( 'maxProperties', $value );
	}
}
