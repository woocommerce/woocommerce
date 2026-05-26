<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Output type representing product physical dimensions.
 */
#[Description( 'Physical dimensions and weight of a product.' )]
class ProductDimensions {
	#[Description( 'The product length.' )]
	public ?float $length;

	#[Description( 'The product width.' )]
	public ?float $width;

	#[Description( 'The product height.' )]
	public ?float $height;

	#[Description( 'The product weight.' )]
	public ?float $weight;
}
