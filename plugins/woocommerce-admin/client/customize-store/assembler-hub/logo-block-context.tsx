/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

export const LogoBlockContext = createContext< {
	logoBlock: {
		clientId: string | null;
		isLoading: boolean;
	};
	setLogoBlock: ( newBlock: {
		clientId: string | null;
		isLoading: boolean;
	} ) => void;
} >( {
	logoBlock: {
		clientId: null,
		isLoading: false,
	},
	setLogoBlock: () => {},
} );
