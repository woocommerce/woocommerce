<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Represents the description of a REST API controller class or endpoint.
 */
#[\Attribute]
class CategorySubtitleAttribute {

	public string $subtitle;

	public function __construct( string $subtitle ) {
		$this->subtitle = $subtitle;
	}
}
