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
	hasBusinessServices: false,
	setHasBusinessServices: () => {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( '' );
	const [ installedPlugins, setInstalledPlugins ] = useState< string[] >(
		[]
	);
	const [ hasBusinessServices, setHasBusinessServices ] = useState( false );

	/**
	 * Knowing installed products will help us to determine which products
	 * should have the "Add to Site" button enabled.
	 */
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
		hasBusinessServices,
		setHasBusinessServices,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
