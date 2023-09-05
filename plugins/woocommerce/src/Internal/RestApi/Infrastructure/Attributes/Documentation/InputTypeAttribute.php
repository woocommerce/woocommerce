<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class InputTypeAttribute {

	public function __construct(
		public string $type_name,
		public InputParameterLocation $location = InputParameterLocation::Body) {
	}
}
