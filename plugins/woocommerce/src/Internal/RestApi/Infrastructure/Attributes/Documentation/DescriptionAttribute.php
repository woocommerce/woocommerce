<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide a description for an item.
 *
 * If a value starting with "::" is provided in the constructor,
 * it's the name of a public static method that will return a string with the description;
 * the syntax is "::ClassName::MethodName", and the class is supposed to be in the
 * Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\TextHolders
 * namespace.
 */
#[\Attribute]
class DescriptionAttribute {
	public function __construct( public string $description ) {}
}
