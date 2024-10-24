/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	TotalsShipping,
	ShippingCalculatorButton,
	ShippingCalculator,
} from '@woocommerce/base-components/cart-checkout';
import { ShippingCalculatorContext } from '@woocommerce/base-components/cart-checkout/shipping-calculator/context';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEditorContext } from '@woocommerce/base-context';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import {
	getShippingRatesPackageCount,
	isPackageRateCollectable,
	isAddressComplete,
	hasShippingRate,
} from '@woocommerce/base-utils';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ShippingRateSelector } from './shipping-rate-selector';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { isEditor } = useEditorContext();
	const {
		cartTotals,
		cartNeedsShipping,
		shippingRates,
		isLoadingRates,
		cartHasCalculatedShipping,
		shippingAddress,
	} = useStoreCart();
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] =
		useState( false );

	if ( ! cartNeedsShipping ) {
		return null;
	}

	if ( isEditor && getShippingRatesPackageCount( shippingRates ) === 0 ) {
		return null;
	}
	const showCalculator = getSetting< boolean >(
		'isShippingCalculatorEnabled',
		true
	);
	const hasCompleteAddress = isAddressComplete( shippingAddress, [
		'state',
		'country',
		'postcode',
		'city',
	] );
	const hasRates =
		cartHasCalculatedShipping && hasShippingRate( shippingRates );
	const isCollectionOnly = hasRates
		? shippingRates.every( ( shippingPackage ) => {
				return shippingPackage.shipping_rates.every(
					( rate ) =>
						! rate.selected || isPackageRateCollectable( rate )
				);
		  } )
		: false;
	const hasCollectionRatesOnly = hasRates
		? shippingRates.every( ( shippingPackage ) => {
				return shippingPackage.shipping_rates.every( ( rate ) =>
					isPackageRateCollectable( rate )
				);
		  } )
		: false;

	return (
		<TotalsWrapper className={ className }>
			<ShippingCalculatorContext.Provider
				value={ {
					showCalculator,
					shippingCalculatorId: 'shipping-calculator-form-wrapper',
					isShippingCalculatorOpen,
					setIsShippingCalculatorOpen,
				} }
			>
				<TotalsShipping
					label={
						isCollectionOnly
							? __( 'Collection', 'woocommerce' )
							: __( 'Delivery', 'woocommerce' )
					}
					hasRates={ hasRates }
					shippingRates={ shippingRates }
					shippingAddress={ shippingAddress }
					values={ cartTotals }
					placeholder={
						showCalculator ? (
							<ShippingCalculatorButton
								label={ __(
									'Enter address to check delivery options',
									'woocommerce'
								) }
							/>
						) : (
							<span className="wc-block-components-shipping-placeholder__value">
								{ __(
									'Calculated during checkout',
									'woocommerce'
								) }
							</span>
						)
					}
					collaterals={
						<>
							{ isShippingCalculatorOpen && (
								<ShippingCalculator />
							) }
							{ ! isShippingCalculatorOpen && (
								<ShippingRateSelector
									shippingRates={ shippingRates }
									isLoadingRates={ isLoadingRates }
									hasCompleteAddress={ hasCompleteAddress }
								/>
							) }
							{ ! showCalculator && hasCollectionRatesOnly && (
								<div className="wc-block-components-totals-shipping__delivery-options-notice">
									{ __(
										'Delivery options will be calculated during checkout',
										'woocommerce'
									) }
								</div>
							) }
						</>
					}
				/>
			</ShippingCalculatorContext.Provider>
		</TotalsWrapper>
	);
};

export default Block;
