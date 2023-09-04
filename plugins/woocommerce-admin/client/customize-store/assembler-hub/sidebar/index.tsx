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
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreenMain } from './sidebar-navigation-screen-main';
import { SidebarNavigationScreenColorPalette } from './sidebar-navigation-screen-color-palette';
import { SidebarNavigationScreenTypography } from './sidebar-navigation-screen-typography';
import { SidebarNavigationScreenHeader } from './sidebar-navigation-screen-header';
import { SidebarNavigationScreenHomepage } from './sidebar-navigation-screen-homepage';
import { SidebarNavigationScreenFooter } from './sidebar-navigation-screen-footer';
import { SidebarNavigationScreenPages } from './sidebar-navigation-screen-pages';
import { SidebarNavigationScreenLogo } from './sidebar-navigation-screen-logo';

import { SaveHub } from './save-hub';

const { useLocation, useHistory } = unlock( routerPrivateApis );

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
	const history = useHistory();
	const { params: urlParams } = useLocation();
	const { location: navigatorLocation, params: navigatorParams } =
		useNavigator();
	const isMounting = useRef( true );

	useEffect(
		() => {
			// The navigatorParams are only initially filled properly when the
			// navigator screens mount. so we ignore the first synchronisation.
			if ( isMounting.current ) {
				isMounting.current = false;
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
				history.push( updatedParams );
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
			<NavigatorScreen path="/customize-store">
				<SidebarNavigationScreenMain />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/color-palette">
				<SidebarNavigationScreenColorPalette />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/typography">
				<SidebarNavigationScreenTypography />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/header">
				<SidebarNavigationScreenHeader />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/homepage">
				<SidebarNavigationScreenHomepage />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/footer">
				<SidebarNavigationScreenFooter />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/pages">
				<SidebarNavigationScreenPages />
			</NavigatorScreen>
			<NavigatorScreen path="/customize-store/logo">
				<SidebarNavigationScreenLogo />
			</NavigatorScreen>
		</>
	);
}

function Sidebar() {
	const { params: urlParams } = useLocation();
	const initialPath = useRef( urlParams.path ?? '/customize-store' );
	return (
		<>
			<NavigatorProvider
				className="edit-site-sidebar__content"
				initialPath={ initialPath.current }
			>
				<SidebarScreens />
			</NavigatorProvider>
			<SaveHub />
		</>
	);
}

export default memo( Sidebar );
