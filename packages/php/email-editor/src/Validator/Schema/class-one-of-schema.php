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
 * Represents a schema that allows a value to match one of the given schemas.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/#oneof-and-anyof
 */
class One_Of_Schema extends Schema {
	/**
	 * Schema definition.
	 *
	 * @var array
	 */
	protected $schema = array(
		'oneOf' => array(),
	);

	/**
	 * One_Of_Schema constructor.
	 *
	 * @param Schema[] $schemas List of schemas.
	 */
	public function __construct(
		array $schemas
	) {
		foreach ( $schemas as $schema ) {
			$this->schema['oneOf'][] = $schema->to_array();
		}
	}

	/**
	 * Sets the schema as nullable.
	 */
	public function nullable(): self {
		$null   = array( 'type' => 'null' );
		$one_of = $this->schema['oneOf'];
		$value  = in_array( $null, $one_of, true ) ? $one_of : array_merge( $one_of, array( $null ) );
		return $this->update_schema_property( 'oneOf', $value );
	}

	/**
	 * Sets the schema as non-nullable.
	 */
	public function non_nullable(): self {
		$null   = array( 'type' => 'null' );
		$one_of = $this->schema['one_of'];
		$value  = array_filter(
			$one_of,
			function ( $item ) use ( $null ) {
				return $item !== $null;
			}
		);
		return $this->update_schema_property( 'one_of', $value );
	}
}
