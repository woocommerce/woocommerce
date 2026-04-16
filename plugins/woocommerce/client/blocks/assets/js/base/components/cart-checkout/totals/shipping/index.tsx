/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsItem } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import type { CartShippingRate } from '@woocommerce/types';
import {
	hasSelectedShippingRate,
	getSelectedShippingRateNames,
} from '@woocommerce/base-utils';
import {
	useStoreCart,
	useOrderSummaryLoadingState,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { ShippingVia } from './shipping-via';
import { renderShippingTotalValue } from './utils';
import './style.scss';

export interface TotalShippingProps {
	label?: string;
	placeholder?: React.ReactNode;
	collaterals?: React.ReactNode;
	shippingRates?: CartShippingRate[];
}

export const TotalsShipping = ( {
	label = __( 'Shipping', 'woocommerce' ),
	placeholder = null,
	collaterals = null,
	shippingRates: shippingRatesProp,
}: TotalShippingProps ): JSX.Element | null => {
	const { cartTotals, shippingRates: cartShippingRates } = useStoreCart();
	const { isLoading } = useOrderSummaryLoadingState();
	const shippingRates = shippingRatesProp ?? cartShippingRates;
	const hasSelectedRates = hasSelectedShippingRate( shippingRates );

	// Fall back to the first available rate name during Ship/Pickup toggles so
	// the label stays meaningful while the total shows a placeholder.
	const selectedNames = getSelectedShippingRateNames( shippingRates );
	const rateNames =
		selectedNames.length > 0
			? selectedNames
			: [ shippingRates[ 0 ]?.shipping_rates[ 0 ]?.name ].filter(
					Boolean
			  );

	const hasMultipleRates = rateNames.length > 1;
	const rowLabel =
		rateNames.length === 0 || hasMultipleRates ? label : rateNames[ 0 ];

	return (
		<div className="wc-block-components-totals-shipping">
			<TotalsItem
				label={ rowLabel }
				value={
					hasSelectedRates
						? renderShippingTotalValue( cartTotals )
						: placeholder
				}
				description={
					<>
						{ hasMultipleRates && <ShippingVia /> }
						{ collaterals && (
							<div className="wc-block-components-totals-shipping__collaterals">
								{ collaterals }
							</div>
						) }
					</>
				}
				currency={ getCurrencyFromPriceResponse( cartTotals ) }
				showSkeleton={ isLoading }
			/>
		</div>
	);
};

export default TotalsShipping;
