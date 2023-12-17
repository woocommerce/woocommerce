/**
 * External dependencies
 */
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { getSetting } from '@woocommerce/settings';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { cartTotals, cartNeedsShipping } = useStoreCart();

	if ( ! cartNeedsShipping ) {
		return null;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<TotalsWrapper className={ className }>
			<TotalsShipping
				showCalculator={ getSetting< boolean >(
					'isShippingCalculatorEnabled',
					true
				) }
				showRateSelector={ true }
				values={ cartTotals }
				currency={ totalsCurrency }
			/>
		</TotalsWrapper>
	);
};

export default Block;
