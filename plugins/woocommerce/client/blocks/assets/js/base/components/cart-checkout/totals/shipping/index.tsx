/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsItem } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { hasShippingRate } from '@woocommerce/base-utils';
import { useStoreCart } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { ShippingVia } from './shipping-via';
import { ShippingAddress } from './shipping-address';
import { renderShippingTotalValue } from './utils';
import './style.scss';

export interface TotalShippingProps {
	label?: string;
	placeholder?: React.ReactNode;
	collaterals?: React.ReactNode;
}

export const TotalsShipping = ( {
	label = __( 'Shipping', 'woocommerce' ),
	placeholder = null,
	collaterals = null,
}: TotalShippingProps ): JSX.Element | null => {
	const { cartTotals, shippingRates } = useStoreCart();
	const hasRates = hasShippingRate( shippingRates );
	return (
		<div className="wc-block-components-totals-shipping">
			<TotalsItem
				label={ label }
				value={
					hasRates
						? renderShippingTotalValue( cartTotals )
						: placeholder
				}
				description={
					<>
						{ !! hasRates && <ShippingVia /> }
						<ShippingAddress />
						{ collaterals && (
							<div className="wc-block-components-totals-shipping__collaterals">
								{ collaterals }
							</div>
						) }
					</>
				}
				currency={ getCurrencyFromPriceResponse( cartTotals ) }
			/>
		</div>
	);
};

export default TotalsShipping;
