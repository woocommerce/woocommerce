/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CheckoutOrderSummaryMobileSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--checkout-order-summary-mobile">
			<div className="wc-block-components-skeleton__row">
				<Skeleton height="25px" width="143px" />
				<Skeleton height="25px" width="53px" />
			</div>
		</div>
	);
};
