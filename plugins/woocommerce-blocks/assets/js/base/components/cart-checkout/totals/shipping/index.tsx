/**
 * External dependencies
 */
import clsx from 'clsx';
import { useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsItem } from '@woocommerce/blocks-components';
import type {
	CartResponseTotals,
	CartShippingRate,
	Currency,
} from '@woocommerce/types';
import {
	ShippingCalculator,
	ShippingCalculatorContext,
} from '@woocommerce/base-components/cart-checkout/shipping-calculator';
import {
	isPackageRateCollectable,
	hasShippingRate,
	getTotalShippingValue,
	isAddressComplete,
} from '@woocommerce/base-utils';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
import { ShippingVia } from './shipping-via';
import { ShippingAddress } from './shipping-address';
import { ShippingRateSelector } from './shipping-rate-selector';
import './style.scss';

export interface TotalShippingProps {
	shippingRates: CartShippingRate[];
	values: CartResponseTotals;
	className?: string;
	placeholder?: React.ReactNode | null;
	showRateSelector?: boolean;
}

const renderShippingTotalValue = ( value: number ) => {
	if ( value === 0 ) {
		return <strong>{ __( 'Free', 'woocommerce' ) }</strong>;
	}
	return value;
};

export const TotalsShipping = ( {
	shippingRates,
	values,
	placeholder = null,
	className,
	showRateSelector = false,
}: TotalShippingProps ): JSX.Element | null => {
	const { shippingAddress, cartHasCalculatedShipping, isLoadingRates } =
		useStoreCart();
	const { isShippingCalculatorOpen } = useContext(
		ShippingCalculatorContext
	);
	const totalShippingValue = getTotalShippingValue( values );
	const totalCurrency = getCurrencyFromPriceResponse( values );
	const hasRates =
		cartHasCalculatedShipping &&
		( hasShippingRate( shippingRates ) || totalShippingValue > 0 );
	const hasCompleteAddress = isAddressComplete( shippingAddress, [
		'state',
		'country',
		'postcode',
		'city',
	] );

	const selectedShippingRates = shippingRates.flatMap(
		( shippingPackage ) => {
			return shippingPackage.shipping_rates
				.filter( ( rate ) => rate.selected )
				.flatMap( ( rate ) => rate.name );
		}
	);
	const isCollectionOnly = hasRates
		? shippingRates.every( ( shippingPackage ) => {
				return shippingPackage.shipping_rates.every(
					( rate ) =>
						! rate.selected || isPackageRateCollectable( rate )
				);
		  } )
		: false;

	return (
		<div
			className={ clsx(
				'wc-block-components-totals-shipping',
				className
			) }
		>
			<TotalsItem
				label={
					isCollectionOnly
						? __( 'Collection', 'woocommerce' )
						: __( 'Delivery', 'woocommerce' )
				}
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
						{ isShippingCalculatorOpen && <ShippingCalculator /> }
						{ ! isShippingCalculatorOpen &&
							showRateSelector &&
							hasRates && (
								<ShippingRateSelector
									shippingRates={ shippingRates }
									isLoadingRates={ isLoadingRates }
									hasCompleteAddress={ hasCompleteAddress }
								/>
							) }
					</>
				}
				currency={ totalCurrency }
			/>
		</div>
	);
};

export default TotalsShipping;
