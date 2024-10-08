/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CalculatorButton, CalculatorButtonProps } from './calculator-button';

export interface ShippingPlaceholderProps {
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	isCheckout?: boolean;
	addressProvided: boolean;
	setIsShippingCalculatorOpen: CalculatorButtonProps[ 'setIsShippingCalculatorOpen' ];
}

export const ShippingPlaceholder = ( {
	showCalculator,
	addressProvided,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	isCheckout = false,
}: ShippingPlaceholderProps ): JSX.Element => {
	if ( ! showCalculator ) {
		const label = addressProvided
			? __( 'No available delivery option', 'woocommerce' )
			: __( 'Enter address to calculate', 'woocommerce' );
		return (
			<span className="wc-block-components-shipping-placeholder__value">
				{ isCheckout
					? label
					: __( 'Calculated during checkout', 'woocommerce' ) }
			</span>
		);
	}

	return (
		<CalculatorButton
			label={ __(
				'Enter address to check delivery options',
				'woocommerce'
			) }
			isShippingCalculatorOpen={ isShippingCalculatorOpen }
			setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
		/>
	);
};

export default ShippingPlaceholder;
