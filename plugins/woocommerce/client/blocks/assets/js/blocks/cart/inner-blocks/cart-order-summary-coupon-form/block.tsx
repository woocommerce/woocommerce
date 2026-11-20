/**
 * External dependencies
 */
import { TotalsCoupon } from '@woocommerce/base-components/cart-checkout';
import { useStoreCartCoupons } from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import type { JSX, ReactNode } from 'react';
import { Children, isValidElement } from '@wordpress/element';

const Block = ( {
	className = '',
	children,
}: {
	className?: string;
	children?: ReactNode;
} ): JSX.Element | null => {
	const couponsEnabled = getSetting( 'couponsEnabled', true );

	const { applyCoupon, isApplyingCoupon } = useStoreCartCoupons( 'wc/cart' );

	if ( ! couponsEnabled ) {
		return null;
	}

	return (
		<TotalsWrapper className={ className }>
			<TotalsCoupon
				onSubmit={ applyCoupon }
				isLoading={ isApplyingCoupon }
				instanceId="coupon"
			>
				{ Children.toArray( children ).some( isValidElement )
					? children
					: undefined }
			</TotalsCoupon>
		</TotalsWrapper>
	);
};

export default Block;
