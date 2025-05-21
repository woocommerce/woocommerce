/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const CheckoutContactSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-contact">
			<Skeleton height="28px" width="177px" />
			<Skeleton />
			<Skeleton width="172px" />
			<Skeleton height="47px" />
		</div>
	);
};
