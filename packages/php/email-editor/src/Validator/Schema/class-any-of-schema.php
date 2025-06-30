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
 * Represents a schema that allows a value to match any of the given schemas.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#oneof-and-anyof
 */
class Any_Of_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array[]
	 */
	protected $schema = array(
		'anyOf' => array(),
	);

	/**
	 * Any_Of_Schema constructor.
	 *
	 * @param Schema[] $schemas List of schemas.
	 */
	public function __construct(
		array $schemas
	) {
		foreach ( $schemas as $schema ) {
			$this->schema['anyOf'][] = $schema->to_array();
		}
	}

	/**
	 * Returns the schema as an array.
	 */
	public function nullable(): self {
		$null   = array( 'type' => 'null' );
		$any_of = $this->schema['anyOf'];
		$value  = in_array( $null, $any_of, true ) ? $any_of : array_merge( $any_of, array( $null ) );
		return $this->update_schema_property( 'anyOf', $value );
	}

	/**
	 * Returns the schema as an array.
	 */
	public function non_nullable(): self {
		$null   = array( 'type' => 'null' );
		$any_of = $this->schema['any_of'];
		$value  = array_filter(
			$any_of,
			function ( $item ) use ( $null ) {
				return $item !== $null;
			}
		);
		return $this->update_schema_property( 'any_of', $value );
	}
}
