/**
 * External dependencies
 */
import {
	useState,
	useEffect,
	useCallback,
	createContext,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SearchResultsCountType, MarketplaceContextType } from './types';
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
	searchResultsCount: {
		extensions: 0,
		themes: 0,
		'business-services': 0,
	},
	setSearchResultsCount: () => {},
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
	const [ searchResultsCount, setSearchResultsCountState ] =
		useState< SearchResultsCountType >( {
			extensions: 0,
			themes: 0,
			'business-services': 0,
		} );

	const setSearchResultsCount = useCallback(
		( updatedCounts: Partial< SearchResultsCountType > ) => {
			setSearchResultsCountState( ( prev ) => ( {
				...prev,
				...updatedCounts,
			} ) );
		},
		[]
	);

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
		searchResultsCount,
		setSearchResultsCount,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
