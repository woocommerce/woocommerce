<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Types\Coupons\DeleteCouponResult;

/**
 * Mutation to delete a coupon.
 */
#[Description( 'Delete a coupon.' )]
#[RequiredCapability( 'manage_woocommerce' )]
class DeleteCoupon {
	/**
	 * Execute the mutation.
	 *
	 * @param int  $id    The coupon ID.
	 * @param bool $force Whether to permanently delete.
	 * @return DeleteCouponResult
	 * @throws ApiException When the coupon is not found.
	 */
	public function execute(
		#[Description( 'The ID of the coupon to delete.' )]
		int $id,
		#[Description( 'Whether to permanently delete the coupon (bypass trash).' )]
		bool $force = false,
	): DeleteCouponResult {
		$wc_coupon = new \WC_Coupon( $id );

		if ( ! $wc_coupon->get_id() ) {
			throw new ApiException( 'Coupon not found.', 'NOT_FOUND', status_code: 404 );
		}

		// Capture the raw return value. A `(bool)` cast would coerce
		// filter-originated `WP_Error` objects to `true`, reporting failure
		// as success; we need to detect that case explicitly and surface
		// the underlying error instead.
		$deleted = $wc_coupon->delete( $force );

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		if ( $deleted instanceof \WP_Error ) {
			throw new ApiException(
				$deleted->get_error_message(),
				'INTERNAL_ERROR',
				status_code: 500,
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$result          = new DeleteCouponResult();
		$result->id      = $id;
		$result->deleted = true === $deleted;

		return $result;
	}
}
