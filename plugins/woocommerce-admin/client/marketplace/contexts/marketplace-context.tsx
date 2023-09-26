/**
 * External dependencies
 */
import { useState, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DEFAULT_TAB_KEY } from '../components/constants';
import { MarketplaceContextType } from './types';

export const MarketplaceContext = createContext< MarketplaceContextType >( {
	isLoading: false,
	setIsLoading: () => {},
	selectedTab: DEFAULT_TAB_KEY,
	setSelectedTab: () => {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( DEFAULT_TAB_KEY );

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
