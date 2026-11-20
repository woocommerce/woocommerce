/**
 * External dependencies
 */
import { CouponButton } from '@woocommerce/base-components/cart-checkout/totals/coupon';
import { useStyleProps } from '@woocommerce/base-hooks';
import type { JSX } from 'react';

export type BlockAttributes = Parameters< typeof useStyleProps >[ 0 ] & {
	className?: string;
};

const Block = ( {
	className,
	...attributes
}: BlockAttributes ): JSX.Element => {
	const buttonStyleProps = useStyleProps( attributes );
	return (
		<div className={ className }>
			<CouponButton buttonStyleProps={ buttonStyleProps } />
		</div>
	);
};

export default Block;
