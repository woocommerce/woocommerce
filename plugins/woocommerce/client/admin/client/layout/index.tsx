/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import {
	unstable_HistoryRouter as HistoryRouter,
	Route,
	Routes,
	useLocation,
	useMatch,
	useParams,
} from 'react-router-dom';
import { identity, isFunction } from 'lodash';
import {
	getHistory,
	getQuery,
	getNewPath,
	navigateTo,
} from '@woocommerce/navigation';
import {
	pluginsStore,
	useUser,
	withPluginsHydration,
	withOptionsHydration,
} from '@woocommerce/data';
import '@woocommerce/notices';
import { SlotFillProvider } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import {
	CustomerEffortScoreModalContainer,
	triggerExitPageCesSurvey,
} from '@woocommerce/customer-effort-score';
import { recordPageView } from '@woocommerce/tracks';
import { PluginArea } from '@wordpress/plugins';
import {
	LayoutContextProvider,
	getLayoutContextValue,
} from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import './style.scss';
import '~/activity-panel';
import '~/mobile-banner';
import { PrimaryLayout } from './shared';

import { usePages, Controller } from './controller';
import { getAdminSetting } from '~/utils/admin-settings';
import { Header } from '../header';
import { Footer } from './footer';
import { TransientNotices } from './transient-notices';
import { usePageClasses, Page } from './hooks/use-page-classes';

const BaseLayout = ( { page }: { page: Page } ) => {
	const { activePlugins, installedPlugins, isJetpackConnected } = useSelect(
		( select ) => {
			const selector = select( pluginsStore );

			return {
				activePlugins: selector.getActivePlugins(),
				isJetpackConnected: selector.isJetpackConnected(),
				installedPlugins: selector.getInstalledPlugins(),
			};
		},
		[]
	);
	const location = useLocation();
	const matchFromRouter = useMatch( location.pathname );
	const params = useParams();
	const match = { params, url: matchFromRouter?.pathname };

	usePageClasses( page );

	function recordPageViewTrack() {
		const { pathname } = location;
		if ( ! pathname ) {
			return;
		}

		// Remove leading slash, and camel case remaining pathname
		let path = pathname.substring( 1 ).replace( /\//g, '_' );

		// When pathname is `/` we are on the home screen.
		if ( path.length === 0 ) {
			path = 'home_screen';
		}

		recordPageView( path, {
			jetpack_installed: installedPlugins.includes( 'jetpack' ),
			jetpack_active: activePlugins.includes( 'jetpack' ),
			jetpack_connected: isJetpackConnected,
		} );
	}

	useEffect( () => {
		triggerExitPageCesSurvey();
	}, [] );

	useEffect( () => {
		recordPageViewTrack();
		setTimeout( () => {
			triggerExitPageCesSurvey();
		}, 0 );
	}, [ location?.pathname ] );

	const {
		breadcrumbs,
		layout = { header: true, footer: true, showPluginArea: true },
	} = page;
	const {
		header: showHeader = true,
		footer: showFooter = true,
		showPluginArea = true,
	} = layout;

	const query = getQuery() as Record< string, string >;

	useEffect( () => {
		const wpbody = document.getElementById( 'wpbody' );
		if ( showHeader ) {
			wpbody?.classList.remove( 'no-header' );
		} else {
			wpbody?.classList.add( 'no-header' );
		}
	}, [ showHeader ] );

	const isDashboardShown =
		query.page && query.page === 'wc-admin' && ! query.path && ! query.task; // ?&task=<x> query param is used to show tasks instead of the homescreen
	useEffect( () => {
		// Catch-all to redirect to LYS hub when it was previously opened.
		const isLYSWaiting =
			window.sessionStorage.getItem( 'lysWaiting' ) === 'yes';
		if ( isDashboardShown && isLYSWaiting ) {
			navigateTo( {
				url: getNewPath( {}, '/launch-your-store' ),
			} );
		}
	}, [ isDashboardShown ] );

	return (
		<LayoutContextProvider
			value={ getLayoutContextValue( [
				page?.navArgs?.id?.toLowerCase() || 'page',
			] ) }
		>
			<SlotFillProvider>
				<div className="woocommerce-layout">
					{ showHeader && (
						<Header
							sections={
								isFunction( breadcrumbs )
									? breadcrumbs( { match } )
									: breadcrumbs
							}
							query={ query }
						/>
					) }
					<TransientNotices />
					<PrimaryLayout
						showNotices={ page?.layout?.showNotices }
						showStoreAlerts={ page?.layout?.showStoreAlerts }
					>
						<div className="woocommerce-layout__main">
							<Controller
								page={ page }
								match={ match }
								query={ query }
							/>
						</div>
					</PrimaryLayout>
					{ showFooter && <Footer /> }
					<CustomerEffortScoreModalContainer />
				</div>
				{ showPluginArea && (
					<>
						<PluginArea scope="woocommerce-admin" />
						<PluginArea scope="woocommerce-tasks" />
					</>
				) }
			</SlotFillProvider>
		</LayoutContextProvider>
	);
};

export const _Layout = () => {
	const { currentUserCan } = useUser();
	const pages = usePages() as Page[];

	// get the basename, usually 'wp-admin/' but can be something else if the site installation changed it
	const path = document.location.pathname;
	const basename = path.substring( 0, path.lastIndexOf( '/' ) );

	return (
		<HistoryRouter history={ getHistory() }>
			{ /* @ts-expect-error basename is not typed */ }
			<Routes basename={ basename }>
				{ pages
					.filter(
						( page ) =>
							! page.capability ||
							currentUserCan( page.capability )
					)
					.map( ( page ) => {
						return (
							<Route
								key={ page.path }
								path={ page.path || '' }
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore Investigate why type error is thrown here
								exact
								element={ <BaseLayout page={ page } /> }
							/>
						);
					} ) }
			</Routes>
		</HistoryRouter>
	);
};

const dataEndpoints = getAdminSetting( 'dataEndpoints' );

export const Layout = compose(
	window.wcSettings.admin
		? withOptionsHydration( {
				...getAdminSetting( 'preloadOptions', {} ),
		  } )
		: identity,
	withPluginsHydration( {
		...getAdminSetting( 'plugins', {} ),
		jetpackStatus:
			( dataEndpoints && dataEndpoints.jetpackStatus ) || false,
	} )
)( _Layout ) as React.ComponentType< Record< string, unknown > >;
