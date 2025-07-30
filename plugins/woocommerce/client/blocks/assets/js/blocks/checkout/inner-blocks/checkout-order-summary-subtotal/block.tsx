/**
 * External dependencies
 */
import { Subtotal, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
} from '@woocommerce/base-context/hooks';

const Block = ( { className = '' }: { className?: string } ): JSX.Element => {
	const { cartTotals } = useStoreCart();
	const { isLoading } = useOrderSummaryLoadingState();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper className={ className }>
			<Subtotal
				currency={ totalsCurrency }
				values={ cartTotals }
				showSkeleton={ isLoading }
			/>
		</TotalsWrapper>
	);
};

export default Block;
