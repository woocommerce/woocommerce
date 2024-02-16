/**
 * External dependencies
 */
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCart,
	useEditorContext,
	useCheckoutAddress,
} from '@woocommerce/base-context/';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { getSetting } from '@woocommerce/settings';
import { getShippingRatesPackageCount } from '@woocommerce/base-utils';
import { select } from '@wordpress/data';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { cartTotals, cartNeedsShipping } = useStoreCart();
	const { isEditor } = useEditorContext();
	const { isShippingAddressReadOnly } = useCheckoutAddress();

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
	const isShippingCalculatorEnabled = getSetting< boolean >(
		'isShippingCalculatorEnabled',
		true
	);
	return (
		<TotalsWrapper className={ className }>
			<TotalsShipping
				showCalculator={
					isShippingCalculatorEnabled && ! isShippingAddressReadOnly
				}
				showRateSelector={ true }
				values={ cartTotals }
				currency={ totalsCurrency }
			/>
		</TotalsWrapper>
	);
};

export default Block;
