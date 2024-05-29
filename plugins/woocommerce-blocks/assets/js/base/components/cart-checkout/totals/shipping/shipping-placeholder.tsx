/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShippingCalculator from '../../shipping-calculator';

export interface ShippingPlaceholderProps {
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	isCheckout?: boolean;
	setIsShippingCalculatorOpen: React.Dispatch<
		React.SetStateAction< boolean >
	>;
}

export const ShippingPlaceholder = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	isCheckout = false,
}: ShippingPlaceholderProps ): JSX.Element => {
	if ( ! showCalculator ) {
		return (
			<span>
				{ isCheckout
					? __( 'No shipping options available', 'woocommerce' )
					: __( 'Calculated during checkout', 'woocommerce' ) }
			</span>
		);
	}

	return (
		<ShippingCalculator
			isShippingCalculatorOpen={ isShippingCalculatorOpen }
			setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
			label={ __(
				'Enter address to check delivery options',
				'woocommerce'
			) }
			onUpdate={ () => {
				setIsShippingCalculatorOpen( false );
			} }
			onCancel={ () => {
				setIsShippingCalculatorOpen( false );
			} }
		/>
	);
};

export default ShippingPlaceholder;
