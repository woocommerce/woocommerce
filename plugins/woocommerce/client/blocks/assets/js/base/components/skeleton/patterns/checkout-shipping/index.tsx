/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutShippingOptionSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-shipping-option">
			<Skeleton height="22px" width="22px" borderRadius="100%" />
			<Skeleton height="22px" maxWidth="148px" />
			<Skeleton height="22px" width="50px" />
		</div>
	);
};
