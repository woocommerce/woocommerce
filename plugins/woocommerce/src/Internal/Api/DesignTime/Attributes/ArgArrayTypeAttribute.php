<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Type of the array items for an array typed argument in an API class method.
 * Alternatively, ArrayTypeAttribute can be applied directly to the argument.
 */
#[Attribute( Attribute::TARGET_METHOD )]
class ArgArrayTypeAttribute {
	public function __construct(
		public string $arg_name,
		public string $class_name,
		public bool $nullable = false
	) {     }
}
