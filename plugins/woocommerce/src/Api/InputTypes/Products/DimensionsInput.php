<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\InputTypes\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\InputTypes\TracksProvidedFields;

/**
 * Input type for product dimensions.
 */
#[Description( 'Physical dimensions and weight for a product.' )]
class DimensionsInput {
	use TracksProvidedFields;

	#[Description( 'The product length.' )]
	public ?float $length = null;

	#[Description( 'The product width.' )]
	public ?float $width = null;

	#[Description( 'The product height.' )]
	public ?float $height = null;

	#[Description( 'The product weight.' )]
	public ?float $weight = null;
}
