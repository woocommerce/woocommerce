/**
 * External dependencies
 */
import { TotalsFooterItem } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import type { Currency, CartResponseTotals } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

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
	className?: string;
	children: JSX.Element | JSX.Element[];
	totalsCurrency: Currency;
	cartTotals: CartResponseTotals;
} ) => {
	return (
		<>
			{ children }
			<div className="wc-block-components-totals-wrapper">
				<TotalsFooterItem
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
			</div>
			<OrderMetaSlotFill />
		</>
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
			<div className={ className }>
				<CheckoutOrderSummary
					totalsCurrency={ totalsCurrency }
					cartTotals={ cartTotals }
				>
					<p
						className="wc-block-components-checkout-order-summary__title-text"
						role="heading"
					>
						{ __( 'Order summary', 'woocommerce' ) }
					</p>
					<>{ children }</>
				</CheckoutOrderSummary>
			</div>
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
