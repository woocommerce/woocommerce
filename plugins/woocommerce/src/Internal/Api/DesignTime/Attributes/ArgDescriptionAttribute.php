<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Description for an argument in an API class method.
 * Alternatively, DescriptionAttribute can be applied directly to the argument.
 */
#[Attribute( Attribute::TARGET_METHOD )]
class ArgDescriptionAttribute {
	public function __construct(
		public string $arg_name,
		public string $description
	) {     }
}
