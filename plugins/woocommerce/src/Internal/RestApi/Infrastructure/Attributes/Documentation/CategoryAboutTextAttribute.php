<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Represents the description of a REST API controller class or endpoint.
 */
#[\Attribute]
class CategoryAboutTextAttribute {

	public string $text;

	public function __construct( string $text ) {
		$this->text = $text;
	}
}
