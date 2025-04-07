/**
 * Internal dependencies
 */
import { Skeleton } from '../..';
import './style.scss';

export const CartOrderSummarySkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--cart-order-summary">
			<div className="wc-block-components-skeleton__row">
				<Skeleton width="173px" />
				<Skeleton width="45px" />
			</div>
			<div className="wc-block-components-skeleton__row">
				<Skeleton width="173px" />
				<Skeleton width="45px" />
			</div>
			<div className="wc-block-components-skeleton__row">
				<Skeleton height="18px" width="112px" />
				<Skeleton height="18px" width="52px" />
			</div>
		</div>
	);
};
