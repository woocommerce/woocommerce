<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Utils\Coupons;

use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType;
use Automattic\WooCommerce\Api\Types\Coupons\Coupon;

/**
 * Maps a WC_Coupon to the Coupon DTO.
 */
class CouponMapper {
	/**
	 * Map a WC_Coupon to the Coupon DTO.
	 *
	 * @param \WC_Coupon $wc_coupon The WooCommerce coupon object.
	 * @return Coupon
	 */
	public static function from_wc_coupon( \WC_Coupon $wc_coupon ): Coupon {
		$coupon = new Coupon();

		$raw_discount_type = (string) $wc_coupon->get_discount_type();
		$raw_status        = (string) $wc_coupon->get_status();

		$coupon->id                          = $wc_coupon->get_id();
		$coupon->code                        = $wc_coupon->get_code();
		$coupon->description                 = $wc_coupon->get_description();
		$coupon->discount_type               = DiscountType::tryFrom( $raw_discount_type ) ?? DiscountType::Other;
		$coupon->raw_discount_type           = $raw_discount_type;
		$coupon->amount                      = (float) $wc_coupon->get_amount();
		$coupon->status                      = '' === $raw_status
			? CouponStatus::Draft
			: ( CouponStatus::tryFrom( $raw_status ) ?? CouponStatus::Other );
		$coupon->raw_status                  = $raw_status;
		$coupon->date_created                = $wc_coupon->get_date_created()?->format( \DateTimeInterface::ATOM );
		$coupon->date_modified               = $wc_coupon->get_date_modified()?->format( \DateTimeInterface::ATOM );
		$coupon->date_expires                = $wc_coupon->get_date_expires()?->format( \DateTimeInterface::ATOM );
		$coupon->usage_count                 = $wc_coupon->get_usage_count();
		$coupon->individual_use              = $wc_coupon->get_individual_use();
		$coupon->product_ids                 = $wc_coupon->get_product_ids();
		$coupon->excluded_product_ids        = $wc_coupon->get_excluded_product_ids();
		$coupon->usage_limit                 = $wc_coupon->get_usage_limit();
		$coupon->usage_limit_per_user        = $wc_coupon->get_usage_limit_per_user();
		$coupon->limit_usage_to_x_items      = $wc_coupon->get_limit_usage_to_x_items();
		$coupon->free_shipping               = $wc_coupon->get_free_shipping();
		$coupon->product_categories          = $wc_coupon->get_product_categories();
		$coupon->excluded_product_categories = $wc_coupon->get_excluded_product_categories();
		$coupon->exclude_sale_items          = $wc_coupon->get_exclude_sale_items();
		$coupon->minimum_amount              = (float) $wc_coupon->get_minimum_amount();
		$coupon->maximum_amount              = (float) $wc_coupon->get_maximum_amount();
		$coupon->email_restrictions          = $wc_coupon->get_email_restrictions();
		$coupon->used_by                     = $wc_coupon->get_used_by();

		return $coupon;
	}
}
