/**
 * External dependencies
 */
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useStoreCart, useEditorContext } from '@woocommerce/base-context/';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { getSetting } from '@woocommerce/settings';
import { getShippingRatesPackageCount } from '@woocommerce/base-utils';
import { select } from '@wordpress/data';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { cartTotals, cartNeedsShipping } = useStoreCart();
	const { isEditor } = useEditorContext();

	if ( ! cartNeedsShipping ) {
		return null;
	}

	const shippingRates = select( 'wc/store/cart' ).getShippingRates();
	const shippingRatesPackageCount =
		getShippingRatesPackageCount( shippingRates );

	if ( ! shippingRatesPackageCount && isEditor ) {
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
