<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide a text for an "About" section.
 */
#[\Attribute]
class AboutTextAttribute {
	public function __construct( public string $text ) {}
}
