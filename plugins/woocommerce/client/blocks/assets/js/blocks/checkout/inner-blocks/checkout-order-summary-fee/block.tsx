/**
 * External dependencies
 */
import { TotalsFees, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryRow,
} from '@woocommerce/base-context/hooks';

const Block = ( {
	className = '',
}: {
	className?: string;
} ): JSX.Element | null => {
	const { cart, wrapperProps } = useOrderSummaryRow(
		useStoreCart(),
		( { cartFees } ) => cartFees.length > 0
	);

	// Hide if there are no fees to show.
	if ( ! cart ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cart.cartTotals );

	return (
		<TotalsWrapper className={ className } { ...wrapperProps }>
			<TotalsFees
				currency={ totalsCurrency }
				cartFees={ cart.cartFees }
			/>
		</TotalsWrapper>
	);
};

export default Block;
