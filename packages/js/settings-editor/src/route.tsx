/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useMemo,
	useState,
	useRef,
	useContext,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	addAction,
	applyFilters,
	didFilter,
	removeAction,
} from '@wordpress/hooks';
/* eslint-disable @woocommerce/dependency-group */
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
/* eslint-enable @woocommerce/dependency-group */

/**
 * Internal dependencies
 */
import { Sidebar } from './components';
import { Route, Location } from './types';
import { LegacyContent } from './legacy';
import { SettingsDataContext } from './data';
const { useLocation } = unlock( routerPrivateApis );

const NotFound = () => {
	return <h1>{ __( 'Page not found', 'woocommerce' ) }</h1>;
};

/**
 * Default route when active page is not found.
 *
 * @param {string}        activePage - The active page.
 * @param {settingsPages} settingsPages      - The settings pages.
 */
const getNotFoundRoute = (
	activePage: string,
	settingsPages: SettingsPages
): Route => ( {
	key: activePage,
	areas: {
		sidebar: (
			<Sidebar
				activePage={ activePage }
				pages={ settingsPages }
				pageTitle={ __( 'Settings', 'woocommerce' ) }
			/>
		),
		content: <NotFound />,
		edit: null,
	},
	widths: {
		content: undefined,
		edit: undefined,
	},
} );

/**
 * Get the tabs for a settings page.
 *
 * @param {settingsPage} settingsPage - The settings page.
 * @return {Array<{ name: string; title: string }>} The tabs.
 */
const getSettingsPageTabs = (
	settingsPage: SettingsPage
): Array< {
	name: string;
	title: string;
} > => {
	const sections = Object.keys( settingsPage.sections );

	return sections.map( ( key ) => ( {
		name: key,
		title: settingsPage.sections[ key ].label,
	} ) );
};

/**
 * Creates a route configuration for legacy settings.
 *
 * @param {string}       activePage    - The active page.
 * @param {string}       activeSection - The active section.
 * @param {settingsPage} settingsPage  - The settings page.
 * @param {settingsData} settingsData  - The settings data.
 */
const getLegacyRoute = (
	activePage: string,
	activeSection: string,
	settingsPage: SettingsPage,
	settingsData: SettingsData
): Route => {
	return {
		key: activePage,
		areas: {
			sidebar: (
				<Sidebar
					activePage={ activePage }
					pages={ settingsData.pages }
					pageTitle={ __( 'Store settings', 'woocommerce' ) }
				/>
			),
			content: (
				<LegacyContent
					settingsData={ settingsData }
					settingsPage={ settingsPage }
					activeSection={ activeSection }
				/>
			),
			edit: null,
		},
		widths: {
			content: undefined,
			edit: undefined,
		},
	};
};

const PAGES_FILTER = 'woocommerce_admin_settings_pages';

const getModernPages = () => {
	/**
	 * Get the modern settings pages.
	 *
	 * @return {Record<string, Route>} The pages.
	 */
	return applyFilters( PAGES_FILTER, {} ) as Record< string, Route >;
};

/**
 * Hook to get the modern settings pages.
 *
 * @return {Record<string, Route>} The pages.
 */
export function useModernRoutes(): Record< string, Route > {
	const [ routes, setRoutes ] = useState< Record< string, Route > >(
		getModernPages()
	);
	const location = useLocation() as Location;
	const isFirstRender = useRef( true );

	/*
	 * Handler for new pages being added after the initial filter has been run,
	 * so that if any routing pages are added later, they can still be rendered
	 * instead of falling back to the `NoMatch` page.
	 */
	useEffect( () => {
		const handleHookAdded = ( hookName: string ) => {
			if ( hookName !== PAGES_FILTER ) {
				return;
			}

			const filterCount = didFilter( PAGES_FILTER );
			if ( filterCount && filterCount > 0 ) {
				setRoutes( getModernPages() );
			}
		};

		const namespace = `woocommerce/woocommerce/watch_${ PAGES_FILTER }`;
		addAction( 'hookAdded', namespace, handleHookAdded );

		return () => {
			removeAction( 'hookAdded', namespace );
		};
	}, [] );

	// Update modern pages when the location changes.
	useEffect( () => {
		if ( isFirstRender.current ) {
			// Prevent updating routes again on first render.
			isFirstRender.current = false;
			return;
		}

		setRoutes( getModernPages() );
	}, [ location.params ] );

	return routes;
}

/**
 * Hook to determine and return the active route based on the current path.
 */
export const useActiveRoute = (): {
	route: Route;
	settingsPage?: SettingsPage;
	activePage?: string;
	activeSection?: string;
	tabs?: Array< { name: string; title: string } >;
} => {
	const { settingsData } = useContext( SettingsDataContext );
	const location = useLocation() as Location;
	const modernRoutes = useModernRoutes();

	return useMemo( () => {
		const { tab: activePage = 'general', section: activeSection } =
			location.params;
		const settingsPage = settingsData?.pages?.[ activePage ];

		if ( ! settingsPage ) {
			return {
				route: getNotFoundRoute( activePage, settingsData.pages ),
			};
		}

		const tabs = getSettingsPageTabs( settingsPage );

		// Handle legacy pages.
		if ( ! settingsPage.is_modern ) {
			return {
				route: getLegacyRoute(
					activePage,
					activeSection || 'default',
					settingsPage,
					settingsData
				),
				settingsPage,
				activePage,
				activeSection,
				tabs,
			};
		}

		const modernRoute = modernRoutes[ activePage ];

		// Handle modern pages.
		if ( ! modernRoute ) {
			return {
				route: getNotFoundRoute( activePage, settingsData.pages ),
			};
		}

		// Sidebar is responsibility of WooCommerce, not extensions so add it here.
		modernRoute.areas.sidebar = (
			<Sidebar
				activePage={ activePage }
				pages={ settingsData.pages }
				pageTitle={ __( 'Store settings', 'woocommerce' ) }
			/>
		);
		// Make sure we have a key.
		modernRoute.key = activePage;

		return {
			route: modernRoute,
			settingsPage,
			activePage,
			activeSection,
			tabs,
		};
	}, [ settingsData, location.params, modernRoutes ] );
};
