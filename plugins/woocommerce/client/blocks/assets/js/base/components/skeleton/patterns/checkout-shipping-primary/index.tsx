/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const CheckoutShippingSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping">
			<Skeleton height="28px" width="177px" />
			<Skeleton width="172px" />
			<Skeleton height="47px" />
		</div>
	);
};
