/**
 * External dependencies
 */
import { CouponInput } from '@woocommerce/base-components/cart-checkout/totals/coupon';
import type { JSX } from 'react';

const Block = ( { className }: { className?: string } ): JSX.Element => (
	<div className={ className }>
		<CouponInput />
	</div>
);

export default Block;
