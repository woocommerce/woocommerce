/**
 * External dependencies
 */
import { Button } from '@ariakit/react';
import { __ } from '@wordpress/i18n';

export interface CalculatorButtonProps {
	label?: string;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: ( isShippingCalculatorOpen: boolean ) => void;
	shippingCalculatorID: string;
}

export const CalculatorButton = ( {
	label = __( 'Calculate', 'woocommerce' ),
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingCalculatorID,
}: CalculatorButtonProps ): JSX.Element => {
	return (
		<Button
			render={ <span /> }
			className="wc-block-components-totals-shipping__change-address__link"
			id="wc-block-components-totals-shipping__change-address__link"
			onClick={ ( e ) => {
				e.preventDefault();
				setIsShippingCalculatorOpen( ! isShippingCalculatorOpen );
			} }
			aria-label={ label }
			aria-expanded={ isShippingCalculatorOpen }
			aria-controls={ shippingCalculatorID }
		>
			{ label }
		</Button>
	);
};

export default CalculatorButton;
