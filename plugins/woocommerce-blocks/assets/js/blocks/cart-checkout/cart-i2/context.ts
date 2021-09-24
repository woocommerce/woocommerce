/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Context consumed by inner blocks.
 */
export type CartBlockContextProps = {
	currentView: string;
};

export const CartBlockContext = createContext< CartBlockContextProps >( {
	currentView: '',
} );

export const useCartBlockContext = (): CartBlockContextProps => {
	return useContext( CartBlockContext );
};
