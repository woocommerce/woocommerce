/**
 * External dependencies
 */
import { useState, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MarketplaceContextType } from './types';
import { Product } from '../components/product-list/types';

export const MarketplaceContext = createContext< MarketplaceContextType >( {
	isLoading: false,
	setIsLoading: () => {},
	selectedTab: '',
	setSelectedTab: () => {},
	products: [],
	setProducts: () => {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( '' );
	const [ products, setProducts ] = useState< Product[] >( [] );

	const contextValue = {
		isLoading,
		setIsLoading,
		selectedTab,
		setSelectedTab,
		products,
		setProducts,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
