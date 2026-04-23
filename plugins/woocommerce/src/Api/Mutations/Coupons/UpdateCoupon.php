<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\InputTypes\Coupons\UpdateCouponInput;
use Automattic\WooCommerce\Api\Utils\Coupons\CouponMapper;
use Automattic\WooCommerce\Api\Types\Coupons\Coupon;

/**
 * Mutation to update an existing coupon.
 */
#[Description( 'Update an existing coupon.' )]
#[RequiredCapability( 'manage_woocommerce' )]
class UpdateCoupon {
	/**
	 * Execute the mutation.
	 *
	 * @param UpdateCouponInput $input The fields to update.
	 * @return Coupon
	 * @throws ApiException When the coupon is not found.
	 */
	public function execute(
		#[Description( 'The fields to update.' )]
		UpdateCouponInput $input,
	): Coupon {
		$wc_coupon = new \WC_Coupon( $input->id );

		if ( ! $wc_coupon->get_id() ) {
			throw new ApiException( 'Coupon not found.', 'NOT_FOUND', status_code: 404 );
		}

		foreach ( array( 'code', 'description', 'amount', 'date_expires', 'individual_use', 'product_ids', 'excluded_product_ids', 'usage_limit', 'usage_limit_per_user', 'limit_usage_to_x_items', 'free_shipping', 'product_categories', 'excluded_product_categories', 'exclude_sale_items', 'minimum_amount', 'maximum_amount', 'email_restrictions' ) as $field ) {
			if ( $input->was_provided( $field ) ) {
				$wc_coupon->{"set_{$field}"}( $input->$field );
			}
		}

		// Nullable enums: only invoke the setter when the client supplied a
		// non-null value. An explicit null means "ignore this field" here —
		// WC_Coupon's enum setters don't accept null and would fall back to
		// their defaults (e.g. 'fixed_cart' for discount_type), silently
		// overwriting whatever is already on the coupon.
		if ( $input->was_provided( 'discount_type' ) && null !== $input->discount_type ) {
			$wc_coupon->set_discount_type( $input->discount_type->value );
		}
		if ( $input->was_provided( 'status' ) && null !== $input->status ) {
			$wc_coupon->set_status( $input->status->value );
		}

		$wc_coupon->save();

		return CouponMapper::from_wc_coupon( $wc_coupon );
	}
}
