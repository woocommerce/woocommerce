/**
 * External dependencies
 */
import { TotalsFees, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart, useTotalsRow } from '@woocommerce/base-context/hooks';

const Block = ( { className }: { className: string } ) => {
	const { cartFees, cartTotals, wrapperProps } = useTotalsRow(
		useStoreCart(),
		className,
		( cart ) => cart.cartFees.length > 0
	);

	// Hide if there are no fees to show.
	if ( ! cartFees.length ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper { ...wrapperProps }>
			<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
		</TotalsWrapper>
	);
};

export default Block;
