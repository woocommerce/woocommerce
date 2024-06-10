/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsItem } from '@woocommerce/blocks-components';
import type { Currency } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import { ShippingVia } from '@woocommerce/base-components/cart-checkout/totals/shipping/shipping-via';
import {
	isAddressComplete,
	isPackageRateCollectable,
} from '@woocommerce/base-utils';
import { useEditorContext } from '@woocommerce/base-context';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	hasShippingRate,
	getTotalShippingValue,
	areShippingMethodsMissing,
} from './utils';
import ShippingCalculator from '../../shipping-calculator';
import ShippingPlaceholder from './shipping-placeholder';
import ShippingAddress from './shipping-address';
import ShippingRateSelector from './shipping-rate-selector';
import './style.scss';

export interface TotalShippingProps {
	currency: Currency;
	values: {
		total_shipping: string;
		total_shipping_tax: string;
	}; // Values in use
	showCalculator?: boolean; //Whether to display the rate selector below the shipping total.
	showRateSelector?: boolean; // Whether to show shipping calculator or not.
	className?: string;
	isCheckout?: boolean;
}

export type ActiveShippingZones = {
	description: string;
}[];

export const TotalsShipping = ( {
	currency,
	values,
	showCalculator = true,
	showRateSelector = true,
	isCheckout = false,
	className,
}: TotalShippingProps ): JSX.Element => {
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] =
		useState( false );
	const {
		shippingAddress,
		cartHasCalculatedShipping,
		shippingRates,
		isLoadingRates,
	} = useStoreCart();
	const { isEditor } = useEditorContext();
	const totalShippingValue = getTotalShippingValue( values );
	const hasRates = hasShippingRate( shippingRates ) || totalShippingValue > 0;
	const showShippingCalculatorForm =
		showCalculator && isShippingCalculatorOpen;
	const prefersCollection = useSelect( ( select ) => {
		return select( CHECKOUT_STORE_KEY ).prefersCollection();
	} );
	const selectedShippingRates = shippingRates.flatMap(
		( shippingPackage ) => {
			return shippingPackage.shipping_rates
				.filter(
					( rate ) =>
						// If the shopper prefers collection, the rate is collectable AND selected.
						( prefersCollection &&
							isPackageRateCollectable( rate ) &&
							rate.selected ) ||
						// Or the shopper does not prefer collection and the rate is selected
						( ! prefersCollection && rate.selected )
				)
				.flatMap( ( rate ) => rate.name );
		}
	);
	const addressComplete = isAddressComplete( shippingAddress );
	const shippingMethodsMissing = areShippingMethodsMissing(
		hasRates,
		prefersCollection,
		shippingRates
	);

	const valueToDisplay =
		totalShippingValue === 0 ? (
			<strong>{ __( 'Free', 'woocommerce' ) }</strong>
		) : (
			totalShippingValue
		);

	const defaultShippingCalculatorLabel = __(
		'Enter address to check delivery options',
		'woocommerce'
	);
	const [ shippingCalculatorLabel, setShippingCalculatorLabel ] = useState(
		defaultShippingCalculatorLabel
	);
	const [ shippingCalculatorAddress, setShippingCalculatorAddress ] =
		useState( '' );

	const activeShippingZones: ActiveShippingZones = getSetting(
		'activeShippingZones'
	);

	const hasMultipleAndDefaultZone =
		activeShippingZones.length > 1 &&
		activeShippingZones.some(
			( zone: { description: string } ) =>
				zone.description === 'Everywhere' ||
				zone.description === 'Locations outside all other zones'
		);

	// If there is no default customer location set in the store,
	// and the customer hasn't provided their address,
	// and only one default shipping method is available for all locations,
	// then the shipping calculator will be hidden to avoid confusion.
	if ( ! addressComplete && ! isEditor && ! hasMultipleAndDefaultZone ) {
		showCalculator = false;
	}
	return (
		<div
			className={ clsx(
				'wc-block-components-totals-shipping',
				className
			) }
		>
			<TotalsItem
				label={ __( 'Delivery', 'woocommerce' ) }
				value={
					! shippingMethodsMissing && cartHasCalculatedShipping
						? // if address is not complete, display the link to add an address.
						  valueToDisplay
						: ( ! addressComplete || isCheckout ) && (
								<ShippingPlaceholder
									showCalculator={ showCalculator }
									isCheckout={ isCheckout }
								/>
						  )
				}
				description={
					( ! shippingMethodsMissing && cartHasCalculatedShipping ) ||
					// If address is complete, display the shipping address.
					( addressComplete && ! isCheckout ) ? (
						<>
							<ShippingVia
								selectedShippingRates={ selectedShippingRates }
							/>
							{ showCalculator && (
								<ShippingAddress
									shippingAddress={ shippingAddress }
									setShippingCalculatorLabel={
										setShippingCalculatorLabel
									}
									setShippingCalculatorAddress={
										setShippingCalculatorAddress
									}
									hasRates={ hasRates }
								/>
							) }
						</>
					) : null
				}
				currency={ currency }
			/>
			{ showCalculator && (
				<ShippingCalculator
					onUpdate={ () => {
						setIsShippingCalculatorOpen( false );
					} }
					onCancel={ () => {
						setIsShippingCalculatorOpen( false );
					} }
					isShippingCalculatorOpen={ isShippingCalculatorOpen }
					setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
					label={ shippingCalculatorLabel }
					shippingCalculatorAddress={ shippingCalculatorAddress }
				/>
			) }
			{ showRateSelector &&
				cartHasCalculatedShipping &&
				! showShippingCalculatorForm && (
					<ShippingRateSelector
						hasRates={ hasRates }
						shippingRates={ shippingRates }
						isLoadingRates={ isLoadingRates }
						isAddressComplete={ addressComplete }
						shippingAddress={ shippingAddress }
						showCalculator={ showCalculator }
					/>
				) }
		</div>
	);
};

export default TotalsShipping;
