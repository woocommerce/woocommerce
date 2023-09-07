<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

/**
 * Attribute to provide a class to serve as the description of the input for a controller endpoint.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class InputTypeAttribute {

	public function __construct(
		public string $type_name,
		public InputParameterLocation $location = InputParameterLocation::Body) {
	}
}
