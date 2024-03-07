// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/sidebar/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { memo, useRef, useEffect } from '@wordpress/element';
import {
	// @ts-ignore No types for this exist yet.
	__experimentalNavigatorProvider as NavigatorProvider,
	// @ts-ignore No types for this exist yet.
	__experimentalNavigatorScreen as NavigatorScreen,
	// @ts-ignore No types for this exist yet.
	__experimentalUseNavigator as useNavigator,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreenMain } from './sidebar-navigation-screen-main';
import { SidebarNavigationScreenColorPalette } from './sidebar-navigation-screen-color-palette';
import { SidebarNavigationScreenTypography } from './sidebar-navigation-screen-typography';
import { SidebarNavigationScreenHeader } from './sidebar-navigation-screen-header';
import { SidebarNavigationScreenHomepage } from './sidebar-navigation-screen-homepage';
import { SidebarNavigationScreenFooter } from './sidebar-navigation-screen-footer';
// import { SidebarNavigationScreenPages } from './sidebar-navigation-screen-pages';
import { SidebarNavigationScreenLogo } from './sidebar-navigation-screen-logo';

import { SaveHub } from './save-hub';
import {
	addHistoryListener,
	getQuery,
	updateQueryString,
	useQuery,
} from '@woocommerce/navigation';

function isSubset(
	subset: {
		[ key: string ]: string | undefined;
	},
	superset: {
		[ key: string ]: string | undefined;
	}
) {
	return Object.entries( subset ).every( ( [ key, value ] ) => {
		return superset[ key ] === value;
	} );
}

function useSyncPathWithURL() {
	const urlParams = useQuery();
	const {
		location: navigatorLocation,
		params: navigatorParams,
		goTo,
	} = useNavigator();
	const isMounting = useRef( true );

	useEffect(
		() => {
			// The navigatorParams are only initially filled properly after the
			// navigator screens mounts. so we don't do the query string update initially.
			// however we also do want to add an event listener for popstate so that we can
			// update the navigator when the user navigates using the browser back button
			if ( isMounting.current ) {
				isMounting.current = false;
				addHistoryListener( ( event: PopStateEvent ) => {
					if ( event.type === 'popstate' ) {
						goTo( ( getQuery() as Record< string, string > ).path );
					}
				} );
				return;
			}

			function updateUrlParams( newUrlParams: {
				[ key: string ]: string | undefined;
			} ) {
				if ( isSubset( newUrlParams, urlParams ) ) {
					return;
				}
				const updatedParams = {
					...urlParams,
					...newUrlParams,
				};
				updateQueryString( {}, updatedParams.path );
			}

			updateUrlParams( {
				postType: undefined,
				postId: undefined,
				categoryType: undefined,
				categoryId: undefined,
				path:
					navigatorLocation.path === '/'
						? undefined
						: navigatorLocation.path,
			} );
		},
		// Trigger only when navigator changes to prevent infinite loops.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ navigatorLocation?.path, navigatorParams ]
	);
}

function SidebarScreens() {
	useSyncPathWithURL();
	return (
		<>
			<NavigatorScreen path="/customize-store/assembler-hub">
				<SidebarNavigationScreenMain />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/assembler-hub/color-palette">
				<SidebarNavigationScreenColorPalette />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/assembler-hub/typography">
				<SidebarNavigationScreenTypography />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/assembler-hub/header">
				<SidebarNavigationScreenHeader />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/assembler-hub/homepage">
				<SidebarNavigationScreenHomepage />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/assembler-hub/footer">
				<SidebarNavigationScreenFooter />
			</NavigatorScreen>
			{ /* TODO: Implement pages sidebar in Phrase 2 */ }
			{ /* <NavigatorScreen path="/customize-store/assembler-hub/pages">
				<SidebarNavigationScreenPages />
			</NavigatorScreen> */ }
			<NavigatorScreen path="/customize-store/assembler-hub/logo">
				<SidebarNavigationScreenLogo />
			</NavigatorScreen>
		</>
	);
}

function Sidebar() {
	const urlParams = getQuery() as Record< string, string >;
	const initialPath = useRef(
		urlParams.path ?? '/customize-store/assembler-hub'
	);
	return (
		<>
			<NavigatorProvider
				className="edit-site-sidebar__content"
				initialPath={ initialPath.current }
			>
				<SidebarScreens />
				<SaveHub />
			</NavigatorProvider>
		</>
	);
}

export default memo( Sidebar );
