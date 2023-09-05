<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

#[\Attribute]
class ArrayTypeAttribute {

	public function __construct(
		public string $type_name) {
	}
}
