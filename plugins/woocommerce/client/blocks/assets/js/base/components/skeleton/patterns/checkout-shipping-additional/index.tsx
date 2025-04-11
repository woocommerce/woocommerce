/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutShippingSkeletonAdditional = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping-additional">
			<Skeleton height="18px" width="18px" borderRadius="100%" />
			<Skeleton height="18px" maxWidth="148px" />
			<Skeleton height="18px" width="50px" />
		</div>
	);
};
