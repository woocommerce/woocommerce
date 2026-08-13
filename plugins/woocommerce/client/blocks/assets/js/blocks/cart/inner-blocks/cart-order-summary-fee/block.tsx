/**
 * External dependencies
 */
import { TotalsFees, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';

const Block = ( { className }: { className: string } ) => {
	const { cartFees, cartTotals } = useStoreCart();

	// Hide if there are no fees to show.
	if ( ! cartFees.length ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper className={ className }>
			<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
		</TotalsWrapper>
	);
};

export default Block;
