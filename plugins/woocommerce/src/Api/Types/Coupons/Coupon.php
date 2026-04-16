<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Types\Coupons;

use Automattic\WooCommerce\Api\Attributes\ArrayOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\ScalarType;
use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType;
use Automattic\WooCommerce\Api\Interfaces\ObjectWithId;
use Automattic\WooCommerce\Api\Scalars\DateTime;

/**
 * Output type representing a WooCommerce coupon.
 */
#[Description( 'Represents a WooCommerce discount coupon.' )]
class Coupon {
	use ObjectWithId;

	#[Description( 'The coupon code.' )]
	public string $code;

	#[Description( 'The coupon description.' )]
	public string $description;

	#[Description( 'The type of discount.' )]
	public DiscountType $discount_type;

	#[Description( 'The raw discount type as stored in WooCommerce. Useful when discount_type is OTHER (e.g. plugin-added types like recurring_percent or sign_up_fee).' )]
	public string $raw_discount_type;

	#[Description( 'The discount amount.' )]
	public float $amount;

	#[Description( 'The coupon status.' )]
	public CouponStatus $status;

	#[Description( 'The raw status as stored in WordPress. Useful when status is OTHER (e.g. plugin-added post statuses).' )]
	public string $raw_status;

	#[Description( 'The date the coupon was created.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_created;

	#[Description( 'The date the coupon was last modified.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_modified;

	#[Description( 'The date the coupon expires.' )]
	#[ScalarType( DateTime::class )]
	public ?string $date_expires;

	#[Description( 'The number of times the coupon has been used.' )]
	public int $usage_count;

	#[Description( 'Whether the coupon can only be used alone.' )]
	public bool $individual_use;

	#[Description( 'Product IDs the coupon can be applied to.' )]
	#[ArrayOf( 'int' )]
	public array $product_ids;

	#[Description( 'Product IDs excluded from the coupon.' )]
	#[ArrayOf( 'int' )]
	public array $excluded_product_ids;

	#[Description( 'Maximum number of times the coupon can be used in total.' )]
	public int $usage_limit;

	#[Description( 'Maximum number of times the coupon can be used per customer.' )]
	public int $usage_limit_per_user;

	#[Description( 'Maximum number of items the coupon can be applied to.' )]
	public ?int $limit_usage_to_x_items;

	#[Description( 'Whether the coupon grants free shipping.' )]
	public bool $free_shipping;

	#[Description( 'Product category IDs the coupon applies to.' )]
	#[ArrayOf( 'int' )]
	public array $product_categories;

	#[Description( 'Product category IDs excluded from the coupon.' )]
	#[ArrayOf( 'int' )]
	public array $excluded_product_categories;

	#[Description( 'Whether the coupon excludes items on sale.' )]
	public bool $exclude_sale_items;

	#[Description( 'Minimum order amount required to use the coupon.' )]
	public float $minimum_amount;

	#[Description( 'Maximum order amount allowed to use the coupon.' )]
	public float $maximum_amount;

	#[Description( 'Email addresses that can use this coupon.' )]
	#[ArrayOf( 'string' )]
	public array $email_restrictions;

	#[Description( 'Email addresses of customers who have used this coupon.' )]
	#[ArrayOf( 'string' )]
	public array $used_by;
}
