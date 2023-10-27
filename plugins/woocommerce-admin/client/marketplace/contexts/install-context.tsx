/**
 * External dependencies
 */
import { useState, createContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SubscriptionsContextType } from './types';

export const InstallContext = createContext< SubscriptionsContextType >( {
	subscriptions: [],
	setSubscriptions: () => {},
	loadSubscriptions: () => new Promise( () => {} ),
	isLoading: true,
	setIsLoading: () => {},
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

	useEffect( () => {
		console.info( 'installingProducts', installingProducts );
	}, [ installingProducts ] );

	const isInstalling = ( productKey: string ) => {
		console.debug( productKey, installingProducts );
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
		subscriptions: [],
		setSubscriptions: () => {},
		loadSubscriptions: () => Promise.resolve(),
		isLoading: true,
		setIsLoading: () => {},
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
