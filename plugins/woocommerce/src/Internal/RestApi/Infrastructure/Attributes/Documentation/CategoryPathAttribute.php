<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Represents the description of a REST API controller class or endpoint.
 */
#[\Attribute]
class CategoryPathAttribute {

	public string $path;

	public function __construct( string $path ) {
		$this->path = $path;
	}
}
