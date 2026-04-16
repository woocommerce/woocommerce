<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Enums\Products;

use Automattic\WooCommerce\Api\Attributes\Description;

#[Description( 'The type of a WooCommerce product.' )]
enum ProductType: string {
	#[Description( 'A simple product.' )]
	case Simple = 'simple';

	#[Description( 'A grouped product.' )]
	case Grouped = 'grouped';

	#[Description( 'An external/affiliate product.' )]
	case External = 'external';

	#[Description( 'A variable product with variations.' )]
	case Variable = 'variable';

	#[Description( 'A product variation.' )]
	case Variation = 'variation';

	#[Description( 'The product type is not one of the standard WooCommerce values (e.g. added by a plugin). Inspect raw_product_type for the underlying value.' )]
	case Other = 'other';
}
