/**
 * Internal dependencies
 */
import { Skeleton } from '../..';

export const CheckoutPaymentSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-payment">
			<Skeleton height="28px" width="177px" />
			<Skeleton width="172px" />
			<Skeleton height="47px" />
		</div>
	);
};
