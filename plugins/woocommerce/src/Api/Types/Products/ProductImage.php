<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Output type representing a product image.
 */
#[Description( 'Represents a product image.' )]
class ProductImage {
	#[Description( 'The image attachment ID.' )]
	public int $id;

	#[Description( 'The image URL.' )]
	public string $url;

	#[Description( 'The image alt text.' )]
	public string $alt;

	#[Description( 'The image display position.' )]
	public int $position;
}
