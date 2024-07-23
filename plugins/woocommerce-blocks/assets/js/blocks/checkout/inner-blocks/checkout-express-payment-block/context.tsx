/**
 * External dependencies
 */
import { useContext, createContext } from '@wordpress/element';

type ExpressCheckoutContextProps = {
	showButtonStyles: boolean;
	buttonHeight: string;
	buttonBorderRadius: string;
};

export const ExpressCheckoutContext: React.Context< ExpressCheckoutContextProps > =
	createContext< ExpressCheckoutContextProps >( {
		showButtonStyles: true,
		buttonHeight: '48',
		buttonBorderRadius: '4',
	} );

export const useExpressCheckoutContext = () => {
	return useContext( ExpressCheckoutContext );
};
