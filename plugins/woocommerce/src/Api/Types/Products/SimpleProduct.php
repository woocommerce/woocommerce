<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Interfaces\Product;

/**
 * Output type representing a simple WooCommerce product.
 */
#[Description( 'A simple WooCommerce product.' )]
class SimpleProduct {
	use Product;
}
