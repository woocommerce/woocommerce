/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Context consumed by inner blocks.
 */
export type CartBlockControlsContextProps = {
	viewSwitcher: {
		component: () => JSX.Element | null;
		currentView: string;
	};
};

export const CartBlockControlsContext = createContext<
	CartBlockControlsContextProps
>( {
	viewSwitcher: {
		component: () => null,
		currentView: 'filledCart',
	},
} );

export const useCartBlockControlsContext = (): CartBlockControlsContextProps => {
	return useContext( CartBlockControlsContext );
};
