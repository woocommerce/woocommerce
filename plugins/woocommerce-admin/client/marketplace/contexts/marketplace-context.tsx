/**
 * External dependencies
 */
import { useState, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MarketplaceContextType } from './types';

export const MarketplaceContext = createContext< MarketplaceContextType >( {
	isLoading: false,
	setIsLoading: () => {},
	selectedTab: '',
	setSelectedTab: () => {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( '' );

	const contextValue = {
		isLoading,
		setIsLoading,
		selectedTab,
		setSelectedTab,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
