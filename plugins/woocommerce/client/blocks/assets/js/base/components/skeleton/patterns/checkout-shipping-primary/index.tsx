/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const CheckoutShippingSkeletonPrimary = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping-primary">
			<Skeleton height="28px" width="177px" />
			<Skeleton width="172px" />
			<Skeleton height="47px" />
		</div>
	);
};
