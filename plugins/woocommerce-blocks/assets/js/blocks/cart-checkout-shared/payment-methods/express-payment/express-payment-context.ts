/**
 * External dependencies
 */
import { useContext, createContext } from '@wordpress/element';

type ExpressPaymentContextProps = {
	showButtonStyles: boolean;
	buttonHeight: string;
	buttonBorderRadius: string;
};

export const ExpressPaymentContext: React.Context< ExpressPaymentContextProps > =
	createContext< ExpressPaymentContextProps >( {
		showButtonStyles: true,
		buttonHeight: '48',
		buttonBorderRadius: '4',
	} );

export const useExpressPaymentContext = () => {
	return useContext( ExpressPaymentContext );
};
