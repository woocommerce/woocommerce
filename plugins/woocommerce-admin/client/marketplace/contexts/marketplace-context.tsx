/**
 * External dependencies
 */
import { useState, useEffect, createContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MarketplaceContextType } from './types';
import { getAdminSetting } from '../../utils/admin-settings';

export const MarketplaceContext = createContext< MarketplaceContextType >( {
	isLoading: false,
	setIsLoading: () => {},
	selectedTab: '',
	setSelectedTab: () => {},
	isProductInstalled: () => false,
	addInstalledProduct: () => {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( '' );
	const [ installedPlugins, setInstalledPlugins ] = useState< string[] >(
		[]
	);

	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		const installedProductSlugs: string[] =
			wccomSettings?.installedProducts;

		setInstalledPlugins( installedProductSlugs );
	}, [] );

	function isProductInstalled( slug: string ): boolean {
		return installedPlugins.includes( slug );
	}

	function addInstalledProduct( slug: string ) {
		setInstalledPlugins( [ ...installedPlugins, slug ] );
	}

	const contextValue = {
		isLoading,
		setIsLoading,
		selectedTab,
		setSelectedTab,
		isProductInstalled,
		addInstalledProduct,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
