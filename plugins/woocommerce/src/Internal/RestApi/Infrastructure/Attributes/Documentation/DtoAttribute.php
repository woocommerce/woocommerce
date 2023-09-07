<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to mark a class as a DTO (Data Transfer Object) to describe input or output
 * for a controller endpoint.
 */
#[\Attribute]
class DtoAttribute {
	public function __construct(
		public bool $include_in_documentation = false,
		public ?string $name = null) {
	}
}
