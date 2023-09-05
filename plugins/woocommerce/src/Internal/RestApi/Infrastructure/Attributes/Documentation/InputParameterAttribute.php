<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class InputParameterAttribute {

	public function __construct(
		public string $name,
		public string $description,
		public InputParameterLocation $location = InputParameterLocation::Query,
		public string $type = 'string',
		public bool $required = false,
		public mixed $default = null ) {
	}
}
