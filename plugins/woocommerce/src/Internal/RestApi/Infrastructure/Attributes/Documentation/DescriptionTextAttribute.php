<?php
/**
 * DescriptionAttribute class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

#[\Attribute]
class DescriptionTextAttribute {

	public string $text;

	public function __construct( string $text ) {
		$this->text = $text;
	}
}
