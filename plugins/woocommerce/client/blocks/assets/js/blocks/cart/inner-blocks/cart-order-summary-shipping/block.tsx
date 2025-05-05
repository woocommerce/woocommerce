/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { TotalsShipping } from '@woocommerce/base-components/cart-checkout';
import { ShippingCalculatorContext } from '@woocommerce/base-components/cart-checkout/shipping-calculator/context';
import { useEditorContext, useStoreCart } from '@woocommerce/base-context';
import { TotalsWrapper } from '@woocommerce/blocks-checkout';
import {
	getShippingRatesPackageCount,
	selectedRatesAreCollectable,
	allRatesAreCollectable,
} from '@woocommerce/base-utils';
import { getSetting } from '@woocommerce/settings';
import { SHIPPING_METHODS_EXIST } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { ShippingRateSelector } from './shipping-rate-selector';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { isEditor } = useEditorContext();
	const { cartNeedsShipping, shippingRates } = useStoreCart();
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] =
		useState( false );

	if ( ! cartNeedsShipping ) {
		return null;
	}

	if ( isEditor && getShippingRatesPackageCount( shippingRates ) === 0 ) {
		return null;
	}

	const showCalculator =
		getSetting< boolean >( 'isShippingCalculatorEnabled', true ) &&
		SHIPPING_METHODS_EXIST;

	const hasSelectedCollectionOnly =
		selectedRatesAreCollectable( shippingRates );

	return (
		<TotalsWrapper className={ className }>
			<ShippingCalculatorContext.Provider
				value={ {
					showCalculator,
					shippingCalculatorID: 'shipping-calculator-form-wrapper',
					isShippingCalculatorOpen,
					setIsShippingCalculatorOpen,
				} }
			>
				<TotalsShipping
					label={
						hasSelectedCollectionOnly
							? __( 'Pickup', 'woocommerce' )
							: __( 'Delivery', 'woocommerce' )
					}
					placeholder={
						! showCalculator ? (
							<span className="wc-block-components-shipping-placeholder__value">
								{ __(
									'Calculated at checkout',
									'woocommerce'
								) }
							</span>
						) : null
					}
					collaterals={
						<>
							<ShippingRateSelector />
							{ ! showCalculator &&
								allRatesAreCollectable( shippingRates ) && (
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
