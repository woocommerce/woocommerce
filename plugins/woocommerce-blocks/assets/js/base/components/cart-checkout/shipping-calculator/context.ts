/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type ShippingCalculatorContextType = {
	shippingCalculatorId: string;
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: ( value: boolean ) => void;
};

export const ShippingCalculatorContext =
	createContext< ShippingCalculatorContextType >( {
		shippingCalculatorId: '',
		showCalculator: false,
		isShippingCalculatorOpen: false,
		setIsShippingCalculatorOpen: () => {
			/* Do nothing */
		},
	} );
