/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { TotalsItem } from '@woocommerce/blocks-components';
import { getTotalShippingValue } from '@woocommerce/base-utils';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import type {
	CartResponseShippingAddress,
	CartResponseTotals,
	CartShippingRate,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { ShippingVia } from './shipping-via';
import { ShippingAddress } from './shipping-address';
import './style.scss';

export interface TotalShippingProps {
	label: string;
	hasRates: boolean;
	shippingRates: CartShippingRate[];
	shippingAddress: CartResponseShippingAddress;
	values: CartResponseTotals;
	className?: string;
	placeholder?: React.ReactNode | null;
	collaterals?: React.ReactNode | null;
}

const renderShippingTotalValue = ( value: number ) => {
	if ( value === 0 ) {
		return <strong>{ __( 'Free', 'woocommerce' ) }</strong>;
	}
	return value;
};

export const TotalsShipping = ( {
	label = __( 'Delivery', 'woocommerce' ),
	hasRates = false,
	shippingAddress,
	shippingRates,
	values,
	placeholder = null,
	className,
	collaterals = null,
}: TotalShippingProps ): JSX.Element | null => {
	const totalShippingValue = getTotalShippingValue( values );
	const totalCurrency = getCurrencyFromPriceResponse( values );
	const selectedShippingRates = shippingRates.flatMap(
		( shippingPackage ) => {
			return shippingPackage.shipping_rates
				.filter( ( rate ) => rate.selected )
				.flatMap( ( rate ) => rate.name );
		}
	);

	return (
		<div
			className={ clsx(
				'wc-block-components-totals-shipping',
				className
			) }
		>
			<TotalsItem
				label={ label }
				value={
					hasRates
						? renderShippingTotalValue( totalShippingValue )
						: placeholder
				}
				description={
					<>
						{ hasRates && (
							<>
								<ShippingVia
									selectedShippingRates={
										selectedShippingRates
									}
								/>
								<ShippingAddress
									shippingAddress={ shippingAddress }
								/>
							</>
						) }
						{ !! collaterals && (
							<div className="wc-block-components-totals-shipping__collaterals">
								{ collaterals }
							</div>
						) }
					</>
				}
				currency={ totalCurrency }
			/>
		</div>
	);
};

export default TotalsShipping;
