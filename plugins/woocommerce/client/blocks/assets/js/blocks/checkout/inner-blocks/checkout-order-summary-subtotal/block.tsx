/**
 * External dependencies
 */
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { TotalsSubtotal } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
} from '@woocommerce/base-context/hooks';

const Block = ( { className = '' }: { className?: string } ): JSX.Element => {
	const { cartItems, cartTotals } = useStoreCart();
	const { isLoading } = useOrderSummaryLoadingState();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper className={ className }>
			<TotalsSubtotal
				currency={ totalsCurrency }
				cartItems={ cartItems }
				cartTotals={ cartTotals }
				showSkeleton={ isLoading }
			/>
		</TotalsWrapper>
	);
};

export default Block;
