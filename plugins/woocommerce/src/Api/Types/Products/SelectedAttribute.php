<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Output type representing a single attribute selection on a variation.
 */
#[Description( 'A selected attribute value on a product variation.' )]
class SelectedAttribute {
	#[Description( 'The attribute name or slug.' )]
	public string $name;

	#[Description( 'The selected attribute value.' )]
	public string $value;
}
