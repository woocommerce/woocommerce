/**
 * External dependencies
 */
import { useState, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InstallContextType } from './types';

export const InstallContext = createContext< InstallContextType >( {
	installingProducts: [],
	isInstalling: () => false,
	addInstalling: () => {},
	removeInstalling: () => {},
} );

export function InstallContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ installingProducts, setInstalling ] = useState< Array< string > >(
		[]
	);

	const isInstalling = ( productKey: string ) => {
		return installingProducts.includes( productKey );
	};
	const addInstalling = ( productKey: string ) => {
		setInstalling( [ ...installingProducts, productKey ] );
	};

	const removeInstalling = ( productKey: string ) => {
		setInstalling(
			[ ...installingProducts ].filter( ( p ) => p !== productKey )
		);
	};

	const contextValue = {
		installingProducts,
		isInstalling,
		addInstalling,
		removeInstalling,
	};

	return (
		<InstallContext.Provider value={ contextValue }>
			{ props.children }
		</InstallContext.Provider>
	);
}
