/**
 * Internal dependencies
 */
import { CartExpressPaymentsSkeleton } from '../../patterns/cart-express-payments';
import { CartLineItemsSkeleton } from '../../patterns/cart-line-items';
import { CartOrderSummarySkeleton } from '../../patterns/cart-order-summary';
import '../../../sidebar-layout/style.scss';
import './style.scss';

export const CartFullSkeleton = () => {
	return (
		<div className="wc-block-components-sidebar-layout">
			<div className="wc-block-components-main">
				<CartLineItemsSkeleton />
			</div>
			<div className="wc-block-components-sidebar">
				<CartOrderSummarySkeleton />
				<CartExpressPaymentsSkeleton />
			</div>
		</div>
	);
};
