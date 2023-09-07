<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide the sidebar path ("Name" or "Name/Subname") for a controller.
 */
#[\Attribute]
class SidebarPathAttribute {
	public function __construct( public string $path ) {}
}
