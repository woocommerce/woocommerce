/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const CheckoutDeliverySkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-delivery">
			<Skeleton height="28px" width="177px" />
			<Skeleton width="172px" />
			<Skeleton height="47px" />
		</div>
	);
};
