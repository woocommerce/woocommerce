<?php

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation;

/**
 * Attribute to provide a title for an "About" section.
 */
#[\Attribute]
class AboutTitleAttribute {
	public function __construct( public string $title ) {}
}
