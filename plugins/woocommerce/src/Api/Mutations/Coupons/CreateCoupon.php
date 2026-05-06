<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\InputTypes\Coupons\CreateCouponInput;
use Automattic\WooCommerce\Api\Utils\Coupons\CouponMapper;
use Automattic\WooCommerce\Api\Types\Coupons\Coupon;

/**
 * Mutation to create a new coupon.
 */
#[Description( 'Create a new coupon.' )]
#[RequiredCapability( 'manage_woocommerce' )]
class CreateCoupon {
	/**
	 * Execute the mutation.
	 *
	 * @param CreateCouponInput $input The coupon creation data.
	 * @return Coupon
	 */
	public function execute(
		#[Description( 'Data for the new coupon.' )]
		CreateCouponInput $input,
	): Coupon {
		$wc_coupon = new \WC_Coupon();
		$wc_coupon->set_code( $input->code );

		foreach ( array( 'description', 'amount', 'date_expires', 'individual_use', 'product_ids', 'excluded_product_ids', 'usage_limit', 'usage_limit_per_user', 'limit_usage_to_x_items', 'free_shipping', 'product_categories', 'excluded_product_categories', 'exclude_sale_items', 'minimum_amount', 'maximum_amount', 'email_restrictions' ) as $field ) {
			if ( null !== $input->$field ) {
				$wc_coupon->{"set_{$field}"}( $input->$field );
			}
		}

		if ( null !== $input->discount_type ) {
			$wc_coupon->set_discount_type( $input->discount_type->value );
		}
		if ( null !== $input->status ) {
			$wc_coupon->set_status( $input->status->value );
		}

		$wc_coupon->save();

		return CouponMapper::from_wc_coupon( $wc_coupon );
	}
}
