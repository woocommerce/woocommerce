<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide the title for the description of an item.
 */
#[\Attribute]
class DescriptionTitleAttribute {
	public function __construct( public string $title ) {}
}
