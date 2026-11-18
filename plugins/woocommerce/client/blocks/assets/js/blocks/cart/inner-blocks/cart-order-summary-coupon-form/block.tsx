/**
 * External dependencies
 */
import { TotalsCoupon } from '@woocommerce/base-components/cart-checkout';
import { useStoreCartCoupons } from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { useStyleProps } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import type { ButtonStyleAttributes } from '../../../cart-checkout-shared/button-style-attributes';

export type BlockAttributes = ButtonStyleAttributes & {
	className?: string;
	displayCouponForm?: boolean;
};

const Block = ( {
	className = '',
	displayCouponForm = false,
	...styleAttributes
}: BlockAttributes ): JSX.Element | null => {
	const buttonStyleProps = useStyleProps( styleAttributes );
	const couponsEnabled = getSetting( 'couponsEnabled', true );

	const { applyCoupon, isApplyingCoupon } = useStoreCartCoupons( 'wc/cart' );

	if ( ! couponsEnabled ) {
		return null;
	}

	return (
		<TotalsWrapper className={ className }>
			<TotalsCoupon
				buttonStyleProps={ buttonStyleProps }
				displayCouponForm={ displayCouponForm }
				onSubmit={ applyCoupon }
				isLoading={ isApplyingCoupon }
				instanceId="coupon"
			/>
		</TotalsWrapper>
	);
};

export default Block;
