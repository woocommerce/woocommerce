/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutPaymentSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-payment">
			<div className="wc-block-components-skeleton--checkout-payment-container">
				<Skeleton height="22px" width="22px" borderRadius="100%" />
				<Skeleton height="22px" maxWidth="148px" />
			</div>
			<Skeleton height="22px" />
		</div>
	);
};
