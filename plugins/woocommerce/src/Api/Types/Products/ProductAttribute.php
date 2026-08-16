<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Output type representing a product attribute definition.
 */
#[Description( 'A product attribute.' )]
class ProductAttribute {
	#[Description( 'The attribute display name.' )]
	public string $name;

	#[Description( 'The attribute taxonomy or key name.' )]
	public string $slug;

	#[Description( 'The available attribute values.' )]
	#[ArrayOf( 'string' )]
	public array $options;

	#[Description( 'The display order position.' )]
	public int $position;

	#[Description( 'Whether the attribute is visible on the product page.' )]
	public bool $visible;

	#[Description( 'Whether the attribute is used for variations.' )]
	public bool $variation;

	#[Description( 'Whether the attribute is a global taxonomy attribute.' )]
	public bool $is_taxonomy;
}
