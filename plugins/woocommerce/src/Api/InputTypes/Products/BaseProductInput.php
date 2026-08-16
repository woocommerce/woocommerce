<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\InputTypes\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\InputTypes\TracksProvidedFields;

/**
 * Shared fields for product creation and update input types.
 */
abstract class BaseProductInput {
	use TracksProvidedFields;

	#[Description( 'The product slug.' )]
	public ?string $slug = null;

	#[Description( 'The product SKU.' )]
	public ?string $sku = null;

	#[Description( 'The full product description.' )]
	public ?string $description = null;

	#[Description( 'The short product description.' )]
	public ?string $short_description = null;

	#[Description( 'The product status.' )]
	public ?ProductStatus $status = null;

	#[Description( 'The product type.' )]
	public ?ProductType $product_type = null;

	#[Description( 'The regular price.' )]
	public ?float $regular_price = null;

	#[Description( 'The sale price.' )]
	public ?float $sale_price = null;

	#[Description( 'Whether to manage stock.' )]
	public ?bool $manage_stock = null;

	#[Description( 'The number of items in stock.' )]
	public ?int $stock_quantity = null;

	#[Description( 'The product dimensions.' )]
	public ?DimensionsInput $dimensions = null;
}
