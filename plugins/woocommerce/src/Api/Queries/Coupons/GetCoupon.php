<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Queries\Coupons;

use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Types\Coupons\Coupon;
use Automattic\WooCommerce\Api\Utils\Coupons\CouponMapper;

#[Name( 'coupon' )]
#[Description( 'Retrieve a single coupon by ID or code. Exactly one of the two arguments must be provided.' )]
/**
 * Query to retrieve a single coupon.
 */
#[RequiredCapability( 'read_private_shop_coupons' )]
class GetCoupon {
	/**
	 * Retrieve a coupon by ID or code.
	 *
	 * @param ?int    $id   The coupon ID.
	 * @param ?string $code The coupon code.
	 * @return ?Coupon
	 * @throws \InvalidArgumentException When neither or both arguments are provided.
	 */
	public function execute(
		#[Description( 'The ID of the coupon to retrieve.' )]
		?int $id = null,
		#[Description( 'The coupon code to look up.' )]
		?string $code = null,
	): ?Coupon {
		if ( ( null === $id ) === ( null === $code ) ) {
			throw new \InvalidArgumentException( 'Exactly one of "id" or "code" must be provided.' );
		}

		$wc_coupon = new \WC_Coupon( $id ?? $code );

		if ( ! $wc_coupon->get_id() ) {
			return null;
		}

		return CouponMapper::from_wc_coupon( $wc_coupon );
	}
}
