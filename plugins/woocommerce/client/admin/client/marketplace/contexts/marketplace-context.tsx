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
import { createStorageUtils } from '../../utils/localStorage';
import {
	MARKETPLACE_HOST,
	MARKETPLACE_IAM_SETTINGS_API_PATH,
} from '../components/constants';

// Create storage utils with 24h expiration
const iamSettingsStorage = createStorageUtils(
	'wc_iam_settings',
	24 * 60 * 60
);

export const MarketplaceContext = createContext< MarketplaceContextType >( {
	isLoading: false,
	setIsLoading: () => {},
	selectedTab: '',
	setSelectedTab: () => {},
	isProductInstalled: () => false,
	addInstalledProduct: () => {},
	searchResultsCount: {
		extensions: 0,
		themes: 0,
		'business-services': 0,
	},
	setSearchResultsCount: () => {},
	iamSettings: {},
} );

export function MarketplaceContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ selectedTab, setSelectedTab ] = useState( '' );
	const [ iamSettings, setIamSettings ] = useState( {} );
	const [ installedPlugins, setInstalledPlugins ] = useState< string[] >(
		[]
	);
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
	 * Load IAM settings from localStorage or WCCOM.
	 */
	useEffect( () => {
		const cachedSettings = iamSettingsStorage.getWithExpiry();
		if ( cachedSettings ) {
			setIamSettings( cachedSettings );
			return;
		}

		const url = `${ MARKETPLACE_HOST }${ MARKETPLACE_IAM_SETTINGS_API_PATH }`;
		fetch( url )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error(
						`Network response was not ok: ${ response.statusText }`
					);
				}
				return response.json();
			} )
			.then( ( data ) => {
				setIamSettings( data );
				iamSettingsStorage.setWithExpiry( data );
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error( 'Failed to fetch IAM settings:', error );
				setIamSettings( {} ); // Fallback to an empty object
			} );
	}, [] );

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
		searchResultsCount,
		setSearchResultsCount,
		iamSettings,
	};

	return (
		<MarketplaceContext.Provider value={ contextValue }>
			{ props.children }
		</MarketplaceContext.Provider>
	);
}
