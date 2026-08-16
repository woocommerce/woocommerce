/**
 * Internal dependencies
 */
import { Skeleton } from '../../';
import './style.scss';

export const CartExpressPaymentsSkeleton = () => {
	return (
		<div className="wc-block-components-skeleton wc-block-components-skeleton--cart-express-payments">
			<Skeleton height="48px" />
			<Skeleton height="48px" />
		</div>
	);
};
