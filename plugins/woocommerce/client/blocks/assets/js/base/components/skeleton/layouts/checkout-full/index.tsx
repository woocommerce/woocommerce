/**
 * Internal dependencies
 */
import { CheckoutOrderSummaryMobileSkeleton } from '../../patterns/checkout-order-summary-mobile';
import { CheckoutExpressPaymentsSkeleton } from '../../patterns/checkout-express-payments';
import { CheckoutContactSkeleton } from '../../patterns/checkout-contact';
import { CheckoutDeliverySkeleton } from '../../patterns/checkout-delivery';
import { CheckoutPaymentSkeleton } from '../../patterns/checkout-payment';
import { CheckoutShippingSkeleton } from '../../patterns/checkout-shipping';
import { CheckoutOrderSummarySkeleton } from '../../patterns/checkout-order-summary';
import '../../../sidebar-layout/style.scss';
import './style.scss';

export const CheckoutFullSkeleton = () => {
	return (
		<div className="wc-block-components-sidebar-layout">
			<div className="wc-block-components-main">
				<CheckoutOrderSummaryMobileSkeleton />
				<CheckoutExpressPaymentsSkeleton />
				<CheckoutContactSkeleton />
				<CheckoutDeliverySkeleton />
				<CheckoutPaymentSkeleton />
				<CheckoutShippingSkeleton />
			</div>
			<div className="wc-block-components-sidebar">
				<CheckoutOrderSummarySkeleton />
			</div>
		</div>
	);
};
