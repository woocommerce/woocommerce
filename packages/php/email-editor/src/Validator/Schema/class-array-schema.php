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
 * Represents a schema for an array.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#arrays
 */
class Array_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'type' => 'array',
	);

	/**
	 * Sets the schema for the items in the array.
	 *
	 * @param Schema $schema Schema for the items in the array.
	 */
	public function items( Schema $schema ): self {
		return $this->update_schema_property( 'items', $schema->to_array() );
	}

	/**
	 * Sets the minimum number of items in the array.
	 *
	 * @param int $value Minimum number of items in the array.
	 */
	public function minItems( int $value ): self {
		return $this->update_schema_property( 'minItems', $value );
	}

	/**
	 * Sets the maximum number of items in the array.
	 *
	 * @param int $value Maximum number of items in the array.
	 */
	public function maxItems( int $value ): self {
		return $this->update_schema_property( 'maxItems', $value );
	}

	/**
	 * Sets the uniqueItems property to true.
	 */
	public function uniqueItems(): self {
		return $this->update_schema_property( 'uniqueItems', true );
	}
}
