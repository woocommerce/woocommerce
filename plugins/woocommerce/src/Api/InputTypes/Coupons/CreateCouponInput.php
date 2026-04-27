<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\InputTypes\Coupons;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType;
use Automattic\WooCommerce\Api\InputTypes\TracksProvidedFields;

/**
 * Input type for creating a coupon.
 */
#[Description( 'Data required to create a new coupon.' )]
class CreateCouponInput {
	use TracksProvidedFields;

	#[Description( 'The coupon code.' )]
	public string $code;

	#[Description( 'The coupon description.' )]
	public ?string $description = null;

	#[Description( 'The type of discount.' )]
	public ?DiscountType $discount_type = null;

	#[Description( 'The discount amount.' )]
	public ?float $amount = null;

	#[Description( 'The coupon status.' )]
	public ?CouponStatus $status = null;

	#[Description( 'The date the coupon expires (ISO 8601).' )]
	public ?string $date_expires = null;

	#[Description( 'Whether the coupon can only be used alone.' )]
	public ?bool $individual_use = null;

	#[Description( 'Product IDs the coupon can be applied to.' )]
	#[ArrayOf( 'int' )]
	public ?array $product_ids = null;

	#[Description( 'Product IDs excluded from the coupon.' )]
	#[ArrayOf( 'int' )]
	public ?array $excluded_product_ids = null;

	#[Description( 'Maximum number of times the coupon can be used in total.' )]
	public ?int $usage_limit = null;

	#[Description( 'Maximum number of times the coupon can be used per customer.' )]
	public ?int $usage_limit_per_user = null;

	#[Description( 'Maximum number of items the coupon can be applied to.' )]
	public ?int $limit_usage_to_x_items = null;

	#[Description( 'Whether the coupon grants free shipping.' )]
	public ?bool $free_shipping = null;

	#[Description( 'Product category IDs the coupon applies to.' )]
	#[ArrayOf( 'int' )]
	public ?array $product_categories = null;

	#[Description( 'Product category IDs excluded from the coupon.' )]
	#[ArrayOf( 'int' )]
	public ?array $excluded_product_categories = null;

	#[Description( 'Whether the coupon excludes items on sale.' )]
	public ?bool $exclude_sale_items = null;

	#[Description( 'Minimum order amount required to use the coupon.' )]
	public ?float $minimum_amount = null;

	#[Description( 'Maximum order amount allowed to use the coupon.' )]
	public ?float $maximum_amount = null;

	#[Description( 'Email addresses that can use this coupon.' )]
	#[ArrayOf( 'string' )]
	public ?array $email_restrictions = null;
}
