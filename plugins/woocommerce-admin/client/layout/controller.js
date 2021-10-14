/**
 * External dependencies
 */
import { Component, Suspense, lazy } from '@wordpress/element';
import { parse, stringify } from 'qs';
import { find, isEqual, last, omit } from 'lodash';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import {
	getNewPath,
	getPersistedQuery,
	getHistory,
	getQueryExcludedScreens,
	getScreenFromPath,
} from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import getReports from '../analytics/report/get-reports';
import { isWCAdmin } from '../dashboard/utils';
import { NoMatch } from './NoMatch';

const AnalyticsReport = lazy( () =>
	import( /* webpackChunkName: "analytics-report" */ '../analytics/report' )
);
const AnalyticsSettings = lazy( () =>
	import(
		/* webpackChunkName: "analytics-settings" */ '../analytics/settings'
	)
);
const Dashboard = lazy( () =>
	import( /* webpackChunkName: "dashboard" */ '../dashboard' )
);

const Homescreen = lazy( () =>
	import( /* webpackChunkName: "homescreen" */ '../homescreen' )
);
const MarketingOverview = lazy( () =>
	import(
		/* webpackChunkName: "marketing-overview" */ '../marketing/overview'
	)
);
const ProfileWizard = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../profile-wizard' )
);
const SettingsGroup = lazy( () =>
	import( /* webpackChunkName: "profile-wizard" */ '../settings' )
);

export const PAGES_FILTER = 'woocommerce_admin_pages_list';

export const getPages = () => {
	const pages = [];
	const initialBreadcrumbs = [
		[ '', getSetting( 'woocommerceTranslation' ) ],
	];

	pages.push( {
		container: Homescreen,
		path: '/',
		breadcrumbs: [
			...initialBreadcrumbs,
			__( 'Home', 'woocommerce-admin' ),
		],
		wpOpenMenu: 'toplevel_page_woocommerce',
		navArgs: {
			id: 'woocommerce-home',
		},
		capability: 'manage_woocommerce',
	} );

	if ( window.wcAdminFeatures.analytics ) {
		pages.push( {
			container: Dashboard,
			path: '/analytics/overview',
			breadcrumbs: [
				...initialBreadcrumbs,
				[
					'/analytics/overview',
					__( 'Analytics', 'woocommerce-admin' ),
				],
				__( 'Overview', 'woocommerce-admin' ),
			],
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			navArgs: {
				id: 'woocommerce-analytics-overview',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsSettings,
			path: '/analytics/settings',
			breadcrumbs: [
				...initialBreadcrumbs,
				[
					'/analytics/revenue',
					__( 'Analytics', 'woocommerce-admin' ),
				],
				__( 'Settings', 'woocommerce-admin' ),
			],
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			navArgs: {
				id: 'woocommerce-analytics-settings',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/customers',
			breadcrumbs: [
				...initialBreadcrumbs,
				__( 'Customers', 'woocommerce-admin' ),
			],
			wpOpenMenu: 'toplevel_page_woocommerce',
			navArgs: {
				id: 'woocommerce-analytics-customers',
			},
			capability: 'view_woocommerce_reports',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/analytics/:report',
			breadcrumbs: ( { match } ) => {
				const report = find( getReports(), {
					report: match.params.report,
				} );
				if ( ! report ) {
					return [];
				}
				return [
					...initialBreadcrumbs,
					[
						'/analytics/revenue',
						__( 'Analytics', 'woocommerce-admin' ),
					],
					report.title,
				];
			},
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-overview',
			capability: 'view_woocommerce_reports',
		} );
	}

	if ( window.wcAdminFeatures.marketing ) {
		pages.push( {
			container: MarketingOverview,
			path: '/marketing',
			breadcrumbs: [
				...initialBreadcrumbs,
				[ '/marketing', __( 'Marketing', 'woocommerce-admin' ) ],
				__( 'Overview', 'woocommerce-admin' ),
			],
			wpOpenMenu: 'toplevel_page_woocommerce-marketing',
			navArgs: {
				id: 'woocommerce-marketing-overview',
			},
			capability: 'view_woocommerce_reports',
		} );
	}

	if ( window.wcAdminFeatures.onboarding ) {
		pages.push( {
			container: ProfileWizard,
			path: '/setup-wizard',
			breadcrumbs: [
				...initialBreadcrumbs,
				[ '/setup-wizard', __( 'Setup Wizard', 'woocommerce-admin' ) ],
			],
			capability: 'manage_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures.settings ) {
		pages.push( {
			container: SettingsGroup,
			path: '/settings/:page',
			breadcrumbs: ( { match } ) => {
				// @todo This might need to be refactored to retreive groups via data store.
				const settingsPages = getSetting( 'settingsPages' );
				const page = settingsPages[ match.params.page ];
				if ( ! page ) {
					return [];
				}
				return [
					...initialBreadcrumbs,
					[
						settingsPages.general
							? '/settings/general'
							: `/settings/${
									Object.keys( settingsPages )[ 0 ]
							  }`,
						__( 'Settings', 'woocommerce-admin' ),
					],
					page,
				];
			},
			wpOpenMenu: 'toplevel_page_woocommerce',
			capability: 'manage_woocommerce',
		} );
	}

	const filteredPages = applyFilters( PAGES_FILTER, pages );

	filteredPages.push( {
		container: NoMatch,
		path: '*',
		breadcrumbs: [
			...initialBreadcrumbs,
			__( 'Not allowed', 'woocommerce-admin' ),
		],
		wpOpenMenu: 'toplevel_page_woocommerce',
	} );

	return filteredPages;
};

export class Controller extends Component {
	componentDidMount() {
		window.document.documentElement.scrollTop = 0;
		window.document.body.classList.remove( 'woocommerce-admin-is-loading' );
	}

	componentDidUpdate( prevProps ) {
		const prevBaseQuery = omit(
			prevProps.query,
			'chartType',
			'filter',
			'paged'
		);
		const baseQuery = omit(
			this.props.query,
			'chartType',
			'filter',
			'paged'
		);

		if (
			prevProps.query.paged > 1 &&
			! isEqual( prevBaseQuery, baseQuery )
		) {
			getHistory().replace( getNewPath( { paged: 1 } ) );
		}

		if ( prevProps.match.url !== this.props.match.url ) {
			window.document.documentElement.scrollTop = 0;
		}
	}

	render() {
		const { page, match, query } = this.props;
		const { url, params } = match;

		window.wpNavMenuUrlUpdate( query );
		window.wpNavMenuClassChange( page, url );
		return (
			<Suspense fallback={ <Spinner /> }>
				<page.container
					params={ params }
					path={ url }
					pathMatch={ page.path }
					query={ query }
				/>
			</Suspense>
		);
	}
}

/**
 * Update an anchor's link in sidebar to include persisted queries. Leave excluded screens
 * as is.
 *
 * @param {HTMLElement} item - Sidebar anchor link.
 * @param {Object} nextQuery - A query object to be added to updated hrefs.
 * @param {Array} excludedScreens - wc-admin screens to avoid updating.
 */
export function updateLinkHref( item, nextQuery, excludedScreens ) {
	if ( isWCAdmin( item.href ) ) {
		const search = last( item.href.split( '?' ) );
		const query = parse( search );
		const path = query.path || 'homescreen';
		const screen = getScreenFromPath( path );

		const isExcludedScreen = excludedScreens.includes( screen );

		const href =
			'admin.php?' +
			stringify(
				Object.assign( query, isExcludedScreen ? {} : nextQuery )
			);

		// Replace the href so you can see the url on hover.
		item.href = href;

		item.onclick = ( e ) => {
			e.preventDefault();
			getHistory().push( href );
		};
	}
}

// Update's wc-admin links in wp-admin menu
window.wpNavMenuUrlUpdate = function ( query ) {
	const nextQuery = getPersistedQuery( query );
	const excludedScreens = getQueryExcludedScreens();

	Array.from(
		document.querySelectorAll( '#adminmenu a' )
	).forEach( ( item ) => updateLinkHref( item, nextQuery, excludedScreens ) );
};

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function ( page, url ) {
	Array.from( document.getElementsByClassName( 'current' ) ).forEach(
		function ( item ) {
			item.classList.remove( 'current' );
		}
	);

	const submenu = Array.from(
		document.querySelectorAll( '.wp-has-current-submenu' )
	);
	submenu.forEach( function ( element ) {
		element.classList.remove( 'wp-has-current-submenu' );
		element.classList.remove( 'wp-menu-open' );
		element.classList.remove( 'selected' );
		element.classList.add( 'wp-not-current-submenu' );
		element.classList.add( 'menu-top' );
	} );

	const pageUrl =
		url === '/'
			? 'admin.php?page=wc-admin'
			: 'admin.php?page=wc-admin&path=' + encodeURIComponent( url );
	const currentItemsSelector =
		url === '/'
			? `li > a[href$="${ pageUrl }"], li > a[href*="${ pageUrl }?"]`
			: `li > a[href*="${ pageUrl }"]`;
	const currentItems = document.querySelectorAll( currentItemsSelector );

	Array.from( currentItems ).forEach( function ( item ) {
		item.parentElement.classList.add( 'current' );
	} );

	if ( page.wpOpenMenu ) {
		const currentMenu = document.querySelector( '#' + page.wpOpenMenu );
		if ( currentMenu ) {
			currentMenu.classList.remove( 'wp-not-current-submenu' );
			currentMenu.classList.add( 'wp-has-current-submenu' );
			currentMenu.classList.add( 'wp-menu-open' );
			currentMenu.classList.add( 'current' );
		}
	}

	const wpWrap = document.querySelector( '#wpwrap' );
	wpWrap.classList.remove( 'wp-responsive-open' );
};
