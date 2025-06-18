/**
 * Internal dependencies
 */
import { CheckoutOrderSummaryMobileSkeleton } from '../../patterns/checkout-order-summary-mobile';
import { CheckoutContactSkeleton } from '../../patterns/checkout-contact';
import { CheckoutDeliverySkeleton } from '../../patterns/checkout-delivery';
import { CheckoutPaymentSkeleton } from '../../patterns/checkout-payment';
import { CheckoutShippingSkeletonPrimary } from '../../patterns/checkout-shipping-primary';
import { CheckoutOrderSummarySkeleton } from '../../patterns/checkout-order-summary';
import '../../../sidebar-layout/style.scss';
import './style.scss';

export const CheckoutSkeleton = () => {
	return (
		<div className="wc-block-components-sidebar-layout">
			<div className="wc-block-components-main">
				<CheckoutOrderSummaryMobileSkeleton />
				<CheckoutContactSkeleton />
				<CheckoutDeliverySkeleton />
				<CheckoutPaymentSkeleton />
				<CheckoutShippingSkeletonPrimary />
			</div>
			<div className="wc-block-components-sidebar">
				<CheckoutOrderSummarySkeleton />
			</div>
		</div>
	);
};
