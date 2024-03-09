<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Enumeration type for an argument in an API class method.
 * Alternatively, EnumTypeAttribute can be applied directly to the argument.
 */
#[Attribute( Attribute::TARGET_METHOD )]
class ArgEnumTypeAttribute {
	public function __construct(
		public string $arg_name,
		public string $class_name
	) {     }
}
