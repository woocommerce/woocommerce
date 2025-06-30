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
 * Represents a schema for an integer.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#numbers
 */
class Integer_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'integer',
	);

	/**
	 * Sets the minimum value of the integer.
	 *
	 * @param int $value Minimum value of the integer.
	 */
	public function minimum( int $value ): self {
		return $this->update_schema_property( 'minimum', $value )
		->unset_schema_property( 'exclusiveMinimum' );
	}

	/**
	 * Sets the exclusiveMinimum property to true.
	 *
	 * @param int $value Minimum value of the integer.
	 */
	public function exclusiveMinimum( int $value ): self {
		return $this->update_schema_property( 'minimum', $value )
		->update_schema_property( 'exclusiveMinimum', true );
	}

	/**
	 * Sets the maximum value of the integer.
	 *
	 * @param int $value Maximum value of the integer.
	 */
	public function maximum( int $value ): self {
		return $this->update_schema_property( 'maximum', $value )
		->unset_schema_property( 'exclusiveMaximum' );
	}

	/**
	 * Sets the exclusiveMaximum property to true.
	 *
	 * @param int $value Maximum value of the integer.
	 */
	public function exclusiveMaximum( int $value ): self {
		return $this->update_schema_property( 'maximum', $value )
		->update_schema_property( 'exclusiveMaximum', true );
	}

	/**
	 * Sets the multipleOf property.
	 *
	 * @param int $value Multiple of the integer.
	 */
	public function multipleOf( int $value ): self {
		return $this->update_schema_property( 'multipleOf', $value );
	}
}
