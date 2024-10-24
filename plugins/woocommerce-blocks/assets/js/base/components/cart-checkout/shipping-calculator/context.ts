/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

type ShippingCalculatorContextType = {
	shippingCalculatorId: string;
	isShippingCalculatorEnabled: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: ( value: boolean ) => void;
};

export const ShippingCalculatorContext =
	createContext< ShippingCalculatorContextType >( {
		shippingCalculatorId: '',
		isShippingCalculatorEnabled: false,
		isShippingCalculatorOpen: false,
		setIsShippingCalculatorOpen: () => {
			/* Do nothing */
		},
	} );
