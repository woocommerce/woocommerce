<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Parameter;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;

/**
 * Output type representing a variable product with variations.
 */
#[Description( 'A variable product with variations.' )]
class VariableProduct {
	use Product;

	#[Description( 'The product variations.' )]
	#[ConnectionOf( ProductVariation::class )]
	#[Parameter( type: PaginationParams::class )]
	public Connection $variations;
}
