<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

/**
 * Class to represent an ISO date and time as a GraphQL scalar type.
 * Assumes that the dates are already represented in ISO format in the database,
 * except that the 'T' is replaced with a space.
 */
class DateTimeType extends ScalarType {

	public function serialize( $value ) {
		return str_replace( ' ', 'T', $value );
	}

	public function parseValue( $value ) {
		return str_replace( 'T', ' ', $value );
	}

	public function parseLiteral( Node $valueNode, ?array $variables = null ) {
		if ( ! $valueNode instanceof StringValueNode ) {
			throw new \Exception( 'Query error: Can only parse strings got: ' . $valueNode->kind, array( $valueNode ) );
		}

		return $valueNode->value;
	}
}
