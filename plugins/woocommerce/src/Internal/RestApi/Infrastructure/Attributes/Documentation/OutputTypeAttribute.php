<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide a class to serve as the description of the output for a controller endpoint.
 */

#[\Attribute]
class OutputTypeAttribute {

	public function __construct(
		public string $type_name) {
	}
}
