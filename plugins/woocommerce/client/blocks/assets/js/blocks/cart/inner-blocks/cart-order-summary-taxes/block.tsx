/**
 * External dependencies
 */
import { TotalsTaxes, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
	useOrderSummaryRow,
} from '@woocommerce/base-context';
import { getSetting } from '@woocommerce/settings';

const Block = ( {
	className,
	showRateAfterTaxName,
}: {
	className: string;
	showRateAfterTaxName: boolean;
} ) => {
	const { isLoading } = useOrderSummaryLoadingState();
	const { cart, wrapperProps } = useOrderSummaryRow(
		useStoreCart(),
		( { cartTotals } ) =>
			! getSetting( 'displayCartPricesIncludingTax', false ) &&
			parseInt( cartTotals.total_tax, 10 ) > 0
	);

	if ( ! cart ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cart.cartTotals );

	return (
		<TotalsWrapper className={ className } { ...wrapperProps }>
			<TotalsTaxes
				showRateAfterTaxName={ showRateAfterTaxName }
				currency={ totalsCurrency }
				values={ cart.cartTotals }
				showSkeleton={ isLoading }
			/>
		</TotalsWrapper>
	);
};

export default Block;
