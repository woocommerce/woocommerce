/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

const ProductLayoutContext = createContext( {
	layoutStyleClassPrefix: '',
} );

export const useProductLayoutContext = () => {
	useContext( ProductLayoutContext );
};
export const ProductLayoutContextProvider = ProductLayoutContext.Provider;
