/**
 * External dependencies
 */
import { Subtotal, TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { Skeleton } from '@woocommerce/base-components/skeleton';

const Block = ( { className = '' }: { className?: string } ): JSX.Element => {
	const { cartTotals, cartIsLoading } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper className={ className }>
			<Subtotal
				currency={ totalsCurrency }
				values={ cartTotals }
				showSkeleton={ cartIsLoading }
				skeleton={ <Skeleton width="45px" height="1em" /> }
			/>
		</TotalsWrapper>
	);
};

export default Block;
