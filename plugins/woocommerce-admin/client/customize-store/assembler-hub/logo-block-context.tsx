/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

export const LogoBlockContext = createContext< {
	clientId: string | null;
	setClientId: ( clientId: string | null ) => void;
} >( {
	clientId: null,
	setClientId: () => {},
} );
