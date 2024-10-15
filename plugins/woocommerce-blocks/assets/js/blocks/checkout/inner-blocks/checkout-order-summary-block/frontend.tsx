/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import type { Currency, CartResponseTotals } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { OrderMetaSlotFill, CheckoutOrderSummaryFill } from './slotfills';

const CheckoutOrderSummary = ( {
	className,
	children,
	totalsCurrency,
	cartTotals,
}: {
	className: string;
	children: JSX.Element | JSX.Element[];
	totalsCurrency: Currency;
	cartTotals: CartResponseTotals;
} ) => {
	return (
		<div className={ className }>
			{ children }
			<div className="wc-block-components-totals-wrapper">
				<TotalsFooterItem
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
			</div>
			<OrderMetaSlotFill />
		</div>
	);
};

const FrontendBlock = ( {
	children,
	className = '',
}: {
	children: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element | null => {
	const { cartTotals } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	// Render once here and once in the fill. The fill can be slotted once elsewhere.
	return (
		<>
			<CheckoutOrderSummary
				className={ className }
				totalsCurrency={ totalsCurrency }
				cartTotals={ cartTotals }
			>
				{ children }
			</CheckoutOrderSummary>
			<CheckoutOrderSummaryFill>
				<CheckoutOrderSummary
					className={ className }
					totalsCurrency={ totalsCurrency }
					cartTotals={ cartTotals }
				>
					{ children }
				</CheckoutOrderSummary>
			</CheckoutOrderSummaryFill>
		</>
	);
};

export default FrontendBlock;
