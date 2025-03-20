/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type ShippingCalculatorContextType = {
	shippingCalculatorID: string;
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: React.Dispatch<
		React.SetStateAction< boolean >
	>;
};

export const ShippingCalculatorContext =
	createContext< ShippingCalculatorContextType >( {
		shippingCalculatorID: '',
		showCalculator: false,
		isShippingCalculatorOpen: false,
		setIsShippingCalculatorOpen: () => {
			/* Do nothing */
		},
	} );
