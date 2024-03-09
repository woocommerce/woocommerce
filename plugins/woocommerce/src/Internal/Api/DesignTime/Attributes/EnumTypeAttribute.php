<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Enumeration type for the return value of an API class method, a property in an object or interface,
 * or an argument in an API class method.
 *
 * For method arguments, ArgEnumTypeAttribute can be used on the method too.
 */
#[Attribute( Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY )]
class EnumTypeAttribute {
	public function __construct(
		public string $class_name
	) {     }
}
