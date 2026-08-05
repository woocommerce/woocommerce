<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Interfaces;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Deprecated;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Ignore;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\Parameter;
use Automattic\WooCommerce\Api\Attributes\ParameterDescription;
use Automattic\WooCommerce\Api\Attributes\ScalarType;
use Automattic\WooCommerce\Api\Enums\Products\ProductStatus;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\Enums\Products\StockStatus;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Scalars\DateTime;
use Automattic\WooCommerce\Api\Types\Products\ProductDimensions;
use Automattic\WooCommerce\Api\Types\Products\ProductAttribute;
use Automattic\WooCommerce\Api\Types\Products\ProductImage;
use Automattic\WooCommerce\Api\Types\Products\ProductReview;

/**
 * Interface trait for WooCommerce products.
 *
 * Defines the common fields shared by all product types.
 */
#[Name( 'Product' )]
#[Description( 'A WooCommerce product.' )]
trait Product {
	use ObjectWithId;

	/**
	 * The product name.
	 *
	 * @var string
	 */
	#[Description( 'The product name.' )]
	public string $name;

	/**
	 * The product slug.
	 *
	 * @var string
	 */
	#[Description( 'The product slug.' )]
	public string $slug;

	/**
	 * The product SKU.
	 *
	 * @var ?string
	 */
	#[Description( 'The product SKU.' )]
	public ?string $sku;

	/**
	 * The full product description.
	 *
	 * @var string
	 */
	#[Description( 'The full product description.' )]
	public string $description;

	/**
	 * The short product description.
	 *
	 * @var string
	 */
	#[Deprecated( 'Use description instead.' )]
	#[Description( 'The short product description.' )]
	public string $short_description;

	/**
	 * The product status.
	 *
	 * @var ProductStatus
	 */
	#[Description( 'The product status.' )]
	public ProductStatus $status;

	/**
	 * The raw status as stored in WordPress. Useful when status is OTHER.
	 *
	 * @var string
	 */
	#[Description( 'The raw status as stored in WordPress. Useful when status is OTHER (e.g. plugin-added post statuses).' )]
	public string $raw_status;

	/**
	 * The product type.
	 *
	 * @var ProductType
	 */
	#[Description( 'The product type.' )]
	public ProductType $product_type;

	/**
	 * The raw product type as stored in WooCommerce. Useful when product_type is OTHER.
	 *
	 * @var string
	 */
	#[Description( 'The raw product type as stored in WooCommerce. Useful when product_type is OTHER (e.g. plugin-added types like subscription, bundle).' )]
	public string $raw_product_type;

	/**
	 * The regular price of the product. Null when not set.
	 *
	 * @var ?string
	 */
	#[Description( 'The regular price of the product. Null when not set.' )]
	#[Parameter( name: 'formatted', type: 'bool', default: true, description: 'Whether to apply currency formatting.' )]
	public ?string $regular_price;

	/**
	 * The sale price of the product.
	 *
	 * @var ?string
	 */
	#[Description( 'The sale price of the product.' )]
	#[Parameter( name: 'formatted', type: 'bool', default: true )]
	#[ParameterDescription( name: 'formatted', description: 'When true, returns price with currency symbol.' )]
	public ?string $sale_price;

	/**
	 * The stock status of the product.
	 *
	 * @var StockStatus
	 */
	#[Description( 'The stock status of the product.' )]
	public StockStatus $stock_status;

	/**
	 * The raw stock status as stored in WooCommerce. Useful when stock_status is OTHER.
	 *
	 * @var string
	 */
	#[Description( 'The raw stock status as stored in WooCommerce. Useful when stock_status is OTHER (e.g. plugin-added statuses).' )]
	public string $raw_stock_status;

	/**
	 * The number of items in stock.
	 *
	 * @var ?int
	 */
	#[Description( 'The number of items in stock.' )]
	public ?int $stock_quantity;

	/**
	 * The product dimensions.
	 *
	 * @var ?ProductDimensions
	 */
	#[Description( 'The product dimensions.' )]
	public ?ProductDimensions $dimensions;

	/**
	 * The product images.
	 *
	 * @var ProductImage[]
	 */
	#[Description( 'The product images.' )]
	#[ArrayOf( ProductImage::class )]
	public array $images;

	/**
	 * The product attributes.
	 *
	 * @var ProductAttribute[]
	 */
	#[Description( 'The product attributes.' )]
	#[ArrayOf( ProductAttribute::class )]
	public array $attributes;

	/**
	 * Customer reviews for this product.
	 *
	 * @var Connection
	 */
	#[Description( 'Customer reviews for this product.' )]
	#[ConnectionOf( ProductReview::class )]
	public Connection $reviews;

	/**
	 * The date the product was created.
	 *
	 * @var ?string
	 */
	#[Description( 'The date the product was created.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_created;

	/**
	 * The date the product was last modified.
	 *
	 * @var ?string
	 */
	#[Description( 'The date the product was last modified.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_modified;

	/**
	 * Internal notes (ignored in schema).
	 *
	 * @var ?string
	 */
	#[Ignore]
	public ?string $internal_notes;
}
