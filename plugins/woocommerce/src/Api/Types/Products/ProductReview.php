<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Products;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\ScalarType;
use Automattic\WooCommerce\Api\Scalars\DateTime;

/**
 * Output type representing a product review.
 */
#[Description( 'Represents a customer review for a product.' )]
class ProductReview {
	#[Description( 'The review ID.' )]
	public int $id;

	#[Description( 'The product ID this review belongs to.' )]
	public int $product_id;

	#[Description( 'The reviewer name.' )]
	public string $reviewer;

	#[Description( 'The review content.' )]
	public string $review;

	#[Description( 'The review rating (1-5).' )]
	public int $rating;

	#[Description( 'The date the review was created.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_created;
}
