<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Represents the description of a REST API controller class or endpoint.
 */
#[\Attribute]
class CategoryTitleAttribute {

	public string $title;

	public function __construct( string $title ) {
		$this->title = $title;
	}
}
