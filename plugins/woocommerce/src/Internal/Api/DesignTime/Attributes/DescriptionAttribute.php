<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Attributes;

use \Attribute;

/**
 * Description of an API class, an API class method, an API class method argument;
 * a property in an enum class, an input type class or an object type class;
 * or a constant in an interface class.
 *
 * For method arguments, ArgDescriptionTypeAttribute can be used on the method too.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS_CONSTANT | Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER )]
class DescriptionAttribute {
	public function __construct(
		public string $description
	) {     }
}
