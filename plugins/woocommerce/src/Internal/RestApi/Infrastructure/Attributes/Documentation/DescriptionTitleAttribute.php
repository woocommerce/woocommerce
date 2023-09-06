<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

#[\Attribute]
class DescriptionTitleAttribute {

	public string $title;

	public function __construct( string $title ) {
		$this->title = $title;
	}
}
