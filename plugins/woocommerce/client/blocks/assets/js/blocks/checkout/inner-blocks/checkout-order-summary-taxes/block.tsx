/**
 * External dependencies
 */
import { TotalsTaxes, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
	useTotalsRow,
} from '@woocommerce/base-context';
import { getSetting } from '@woocommerce/settings';

const Block = ( {
	className,
	showRateAfterTaxName,
}: {
	className: string;
	showRateAfterTaxName: boolean;
} ): JSX.Element | null => {
	const { isLoading } = useOrderSummaryLoadingState();
	const displayCartPricesIncludingTax = getSetting(
		'displayCartPricesIncludingTax',
		false
	);
	const { cartTotals, wrapperProps } = useTotalsRow(
		useStoreCart(),
		className,
		( cart ) =>
			! displayCartPricesIncludingTax &&
			parseInt( cart.cartTotals.total_tax, 10 ) > 0
	);

	if (
		displayCartPricesIncludingTax ||
		parseInt( cartTotals.total_tax, 10 ) <= 0
	) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper { ...wrapperProps }>
			<TotalsTaxes
				showRateAfterTaxName={ showRateAfterTaxName }
				currency={ totalsCurrency }
				values={ cartTotals }
				showSkeleton={ isLoading }
			/>
		</TotalsWrapper>
	);
};

export default Block;
