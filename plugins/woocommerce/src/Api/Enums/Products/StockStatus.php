<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Enums\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'The stock status of a product.' )]
enum StockStatus: int {
	#[Description( 'The product is in stock.' )]
	case InStock = 1;

	#[Description( 'The product is out of stock.' )]
	case OutOfStock = 2;

	#[Description( 'The product is on backorder.' )]
	case OnBackorder = 3;
}
