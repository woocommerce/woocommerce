<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator;

use Automattic\WooCommerce\EmailEditor\Validator\Schema\Any_Of_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Array_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Boolean_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Integer_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Null_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Number_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\Object_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\One_Of_Schema;
use Automattic\WooCommerce\EmailEditor\Validator\Schema\String_Schema;

/**
 * Builder for creating schema objects.
 * See: https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
 */
class Builder {
	/**
	 * Creates a schema for a string.
	 */
	public static function string(): String_Schema {
		return new String_Schema();
	}

	/**
	 * Creates a schema for a number.
	 */
	public static function number(): Number_Schema {
		return new Number_Schema();
	}

	/**
	 * Creates a schema for an integer.
	 */
	public static function integer(): Integer_Schema {
		return new Integer_Schema();
	}

	/**
	 * Creates a schema for a boolean.
	 */
	public static function boolean(): Boolean_Schema {
		return new Boolean_Schema();
	}

	/**
	 * Creates a schema for null.
	 */
	public static function null(): Null_Schema {
		return new Null_Schema();
	}

	/**
	 * Creates a schema for an array.
	 *
	 * @param Schema|null $items Schema of the items in the array.
	 */
	public static function array( ?Schema $items = null ): Array_Schema {
		$array = new Array_Schema();
		return $items ? $array->items( $items ) : $array;
	}

	/**
	 * Creates a schema for an object.
	 *
	 * @param array<string, Schema>|null $properties Properties of the object.
	 */
	public static function object( ?array $properties = null ): Object_Schema {
		$object = new Object_Schema();
		return null === $properties ? $object : $object->properties( $properties );
	}

	/**
	 * Creates a schema that allows a value to match one of the given schemas.
	 *
	 * @param Schema[] $schemas List of schemas.
	 */
	public static function one_of( array $schemas ): One_Of_Schema {
		return new One_Of_Schema( $schemas );
	}

	/**
	 * Creates a schema that allows a value to match any of the given schemas.
	 *
	 * @param Schema[] $schemas List of schemas.
	 */
	public static function any_of( array $schemas ): Any_Of_Schema {
		return new Any_Of_Schema( $schemas );
	}
}
