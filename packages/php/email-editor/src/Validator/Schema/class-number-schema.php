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
 * Represents a schema for a number.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#numbers
 */
class Number_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'number',
	);

	/**
	 * Sets the minimum value of the number.
	 *
	 * @param float $value Minimum value of the number.
	 */
	public function minimum( float $value ): self {
		return $this->update_schema_property( 'minimum', $value )
		->unset_schema_property( 'exclusiveMinimum' );
	}

	/**
	 * Sets the exclusiveMinimum property to true.
	 *
	 * @param float $value Minimum value of the number.
	 */
	public function exclusiveMinimum( float $value ): self {
		return $this->update_schema_property( 'minimum', $value )
		->update_schema_property( 'exclusiveMinimum', true );
	}

	/**
	 * Sets the maximum value of the number.
	 *
	 * @param float $value Maximum value of the number.
	 */
	public function maximum( float $value ): self {
		return $this->update_schema_property( 'maximum', $value )
		->unset_schema_property( 'exclusiveMaximum' );
	}

	/**
	 * Sets the exclusiveMaximum property to true.
	 *
	 * @param float $value Maximum value of the number.
	 */
	public function exclusiveMaximum( float $value ): self {
		return $this->update_schema_property( 'maximum', $value )
		->update_schema_property( 'exclusiveMaximum', true );
	}

	/**
	 * Sets the multipleOf property.
	 *
	 * @param float $value Multiple of the number.
	 */
	public function multipleOf( float $value ): self {
		return $this->update_schema_property( 'multipleOf', $value );
	}
}
