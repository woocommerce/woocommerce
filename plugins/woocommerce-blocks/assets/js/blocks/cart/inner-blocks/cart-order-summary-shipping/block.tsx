/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	TotalsShipping,
	ShippingCalculatorButton,
} from '@woocommerce/base-components/cart-checkout';
import { ShippingCalculatorContext } from '@woocommerce/base-components/cart-checkout/shipping-calculator/context';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useEditorContext } from '@woocommerce/base-context';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { getShippingRatesPackageCount } from '@woocommerce/base-utils';
import { getSetting } from '@woocommerce/settings';

const Block = ( { className }: { className: string } ): JSX.Element | null => {
	const { isEditor } = useEditorContext();
	const { cartTotals, cartNeedsShipping, shippingRates } = useStoreCart();
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
					shippingRates={ shippingRates }
					values={ cartTotals }
					showRateSelector={ true }
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
				/>
			</ShippingCalculatorContext.Provider>
		</TotalsWrapper>
	);
};

export default Block;
