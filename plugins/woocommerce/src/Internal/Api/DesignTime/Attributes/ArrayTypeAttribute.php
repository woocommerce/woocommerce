<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Type of the array items for an array typed argument in an API class method,
 * for an array typed return type of an API class method, or for an array typed property
 * in an object or interface.
 *
 * For method arguments, ArgArrayTypeAttribute can be used on the method too.
 */
#[Attribute( Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY )]
class ArrayTypeAttribute {
	public function __construct(
		public string $class_name,
		public bool $nullable = false
	) {     }
}
