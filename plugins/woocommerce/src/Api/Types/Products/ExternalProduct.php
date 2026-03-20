<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Interfaces\Product;

/**
 * Output type representing an external/affiliate product.
 */
#[Description( 'An external/affiliate product.' )]
class ExternalProduct {
	use Product;

	#[Description( 'The external product URL.' )]
	public ?string $product_url;

	#[Description( 'The text for the external product button.' )]
	public ?string $button_text;
}
