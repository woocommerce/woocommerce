/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export interface CalculatorButtonProps {
	label?: string;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: ( isShippingCalculatorOpen: boolean ) => void;
}

export const CalculatorButton = ( {
	label = __( 'Calculate', 'woo-gutenberg-products-block' ),
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
}: CalculatorButtonProps ): JSX.Element => {
	return (
		<button
			className="wc-block-components-totals-shipping__change-address-button"
			onClick={ () => {
				setIsShippingCalculatorOpen( ! isShippingCalculatorOpen );
			} }
			aria-expanded={ isShippingCalculatorOpen }
		>
			{ label }
		</button>
	);
};

export default CalculatorButton;
