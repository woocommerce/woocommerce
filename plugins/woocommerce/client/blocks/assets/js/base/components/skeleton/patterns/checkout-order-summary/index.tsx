/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import { CartLineItemsSkeleton } from '../cart-line-items';
import './style.scss';

export const CheckoutOrderSummarySkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-order-summary">
			<div className="wc-block-components-skeleton__row">
				<Skeleton width="196px" height="28px" />
			</div>
			<div className="wc-block-components-skeleton__row wc-block-cart-item">
				<CartLineItemsSkeleton />
			</div>
			<div className="wc-block-components-skeleton__row">
				<Skeleton width="50%" />
				<Skeleton width="15%" />
			</div>
			<div className="wc-block-components-skeleton__row">
				<Skeleton width="50%" />
				<Skeleton width="15%" />
			</div>
			<div className="wc-block-components-skeleton__row">
				<Skeleton height="2.5em" width="30%" />
				<Skeleton height="2.5em" width="15%" />
			</div>
		</div>
	);
};
