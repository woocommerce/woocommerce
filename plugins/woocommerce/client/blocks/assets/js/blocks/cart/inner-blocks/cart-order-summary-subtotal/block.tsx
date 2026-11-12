/**
 * External dependencies
 */
import { Subtotal, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
	useOrderSummaryRow,
} from '@woocommerce/base-context/hooks';

const Block = ( { className = '' }: { className?: string } ) => {
	const { isLoading } = useOrderSummaryLoadingState();
	// Shown only when there are other totals to show, so it grows and collapses with them.
	const { cart, wrapperProps } = useOrderSummaryRow(
		useStoreCart(),
		( { cartTotals } ) =>
			!! parseFloat( cartTotals.total_fees ) ||
			!! parseFloat( cartTotals.total_discount ) ||
			!! parseFloat( cartTotals.total_shipping )
	);

	if ( ! cart ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cart.cartTotals );

	return (
		<TotalsWrapper className={ className } { ...wrapperProps }>
			<Subtotal
				currency={ totalsCurrency }
				values={ cart.cartTotals }
				showSkeleton={ isLoading }
			/>
		</TotalsWrapper>
	);
};

export default Block;
