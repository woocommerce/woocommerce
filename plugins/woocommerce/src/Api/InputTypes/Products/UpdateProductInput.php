<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\InputTypes\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

/**
 * Input type for updating a product.
 */
#[Description( 'Data for updating an existing product.' )]
class UpdateProductInput extends BaseProductInput {
	#[Description( 'The ID of the product to update.' )]
	public int $id;

	#[Description( 'The product name.' )]
	public ?string $name = null;
}
