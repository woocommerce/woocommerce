<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

#[\Attribute]
class DtoAttribute {

	public function __construct(
		public bool $include_in_documentation = false,
		public ?string $name = null) {
	}
}
