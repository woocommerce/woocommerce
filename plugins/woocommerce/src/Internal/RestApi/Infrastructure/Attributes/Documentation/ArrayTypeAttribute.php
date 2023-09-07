<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to indicate the type of the items in the array, when an InputTypeAttribute
 * or an OutputTypeAttribute indicates that a property is of type "array".
 */
#[\Attribute]
class ArrayTypeAttribute {
	public function __construct( public string $type_name ) {}
}
