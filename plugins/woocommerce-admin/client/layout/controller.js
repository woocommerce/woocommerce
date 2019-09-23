/** @format */
/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';
import { parse, stringify } from 'qs';
import { find, isEqual, last, omit } from 'lodash';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { getNewPath, getPersistedQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AnalyticsReport, { getReports } from 'analytics/report';
import AnalyticsSettings from 'analytics/settings';
import Dashboard from 'dashboard';
import DevDocs from 'devdocs';

const TIME_EXCLUDED_SCREENS_FILTER = 'woocommerce_admin_time_excluded_screens';

export const PAGES_FILTER = 'woocommerce_admin_pages_list';

export const getPages = () => {
	const pages = [];

	if ( window.wcAdminFeatures.devdocs ) {
		pages.push( {
			container: DevDocs,
			path: '/devdocs',
			breadcrumbs: ( { location } ) => {
				const searchParams = new URLSearchParams( location.search );
				const component = searchParams.get( 'component' );

				if ( component ) {
					return [ [ '/devdocs', 'Documentation' ], component ];
				}

				return [ 'Documentation' ];
			},
			wpOpenMenu: 'toplevel_page_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures[ 'analytics-dashboard' ] ) {
		pages.push( {
			container: Dashboard,
			path: '/',
			breadcrumbs: [ __( 'Dashboard', 'woocommerce-admin' ) ],
			wpOpenMenu: 'toplevel_page_woocommerce',
		} );
	}

	if ( window.wcAdminFeatures.analytics ) {
		pages.push( {
			container: AnalyticsSettings,
			path: '/analytics/settings',
			breadcrumbs: [
				[ '/analytics/revenue', __( 'Analytics', 'woocommerce-admin' ) ],
				__( 'Settings', 'woocommerce-admin' ),
			],
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-revenue',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/analytics/:report',
			breadcrumbs: ( { match } ) => {
				const report = find( getReports(), { report: match.params.report } );
				if ( ! report ) {
					return [];
				}
				return [ [ '/analytics/revenue', __( 'Analytics', 'woocommerce-admin' ) ], report.title ];
			},
			wpOpenMenu: 'toplevel_page_wc-admin-path--analytics-revenue',
		} );
	}

	return applyFilters( PAGES_FILTER, pages );
};

export class Controller extends Component {
	componentDidMount() {
		window.document.documentElement.scrollTop = 0;
	}

	componentDidUpdate( prevProps ) {
		const prevQuery = this.getQuery( prevProps.location.search );
		const prevBaseQuery = omit( this.getQuery( prevProps.location.search ), 'paged' );
		const baseQuery = omit( this.getQuery( this.props.location.search ), 'paged' );

		if ( prevQuery.paged > 1 && ! isEqual( prevBaseQuery, baseQuery ) ) {
			getHistory().replace( getNewPath( { paged: 1 } ) );
		}

		if ( prevProps.match.url !== this.props.match.url ) {
			window.document.documentElement.scrollTop = 0;
		}
	}

	getQuery( searchString ) {
		if ( ! searchString ) {
			return {};
		}

		const search = searchString.substring( 1 );
		return parse( search );
	}

	render() {
		const { page, match, location } = this.props;
		const { url, params } = match;
		const query = this.getQuery( location.search );

		window.wpNavMenuUrlUpdate( page, query );
		window.wpNavMenuClassChange( page, url );
		return createElement( page.container, { params, path: url, pathMatch: page.path, query } );
	}
}

/**
 * Update an anchor's link in sidebar to include persisted queries. Leave excluded screens
 * as is.
 *
 * @param {HTMLElement} item - Sidebar anchor link.
 * @param {object} nextQuery - A query object to be added to updated hrefs.
 * @param {Array} excludedScreens - wc-admin screens to avoid updating.
 */
export function updateLinkHref( item, nextQuery, excludedScreens ) {
	const isWCAdmin = /admin.php\?page=wc-admin/.test( item.href );

	if ( isWCAdmin ) {
		const search = last( item.href.split( '?' ) );
		const query = parse( search );
		const path = query.path || 'dashboard';
		const screen = path.replace( '/analytics', '' ).replace( '/', '' );

		const isExcludedScreen = excludedScreens.includes( screen );

		const href =
			'admin.php?' + stringify( Object.assign( query, isExcludedScreen ? {} : nextQuery ) );

		// Replace the href so you can see the url on hover.
		item.href = href;

		item.onclick = e => {
			e.preventDefault();
			getHistory().push( href );
		};
	}
}

// Update's wc-admin links in wp-admin menu
window.wpNavMenuUrlUpdate = function( page, query ) {
	const excludedScreens = applyFilters( TIME_EXCLUDED_SCREENS_FILTER, [
		'devdocs',
		'stock',
		'settings',
		'customers',
	] );
	const nextQuery = getPersistedQuery( query );

	Array.from( document.querySelectorAll( '#adminmenu a' ) ).forEach( item =>
		updateLinkHref( item, nextQuery, excludedScreens )
	);
};

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function( page, url ) {
	Array.from( document.getElementsByClassName( 'current' ) ).forEach( function( item ) {
		item.classList.remove( 'current' );
	} );

	const submenu = Array.from( document.querySelectorAll( '.wp-has-current-submenu' ) );
	submenu.forEach( function( element ) {
		element.classList.remove( 'wp-has-current-submenu' );
		element.classList.remove( 'wp-menu-open' );
		element.classList.remove( 'selected' );
		element.classList.add( 'wp-not-current-submenu' );
		element.classList.add( 'menu-top' );
	} );

	const pageUrl =
		'/' === url
			? 'admin.php?page=wc-admin'
			: 'admin.php?page=wc-admin&path=' + encodeURIComponent( url );
	const currentItemsSelector =
		url === '/'
			? `li > a[href$="${ pageUrl }"], li > a[href*="${ pageUrl }?"]`
			: `li > a[href*="${ pageUrl }"]`;
	const currentItems = document.querySelectorAll( currentItemsSelector );

	Array.from( currentItems ).forEach( function( item ) {
		item.parentElement.classList.add( 'current' );
	} );

	if ( page.wpOpenMenu ) {
		const currentMenu = document.querySelector( '#' + page.wpOpenMenu );
		currentMenu.classList.remove( 'wp-not-current-submenu' );
		currentMenu.classList.add( 'wp-has-current-submenu' );
		currentMenu.classList.add( 'wp-menu-open' );
		currentMenu.classList.add( 'current' );
	}

	const wpWrap = document.querySelector( '#wpwrap' );
	wpWrap.classList.remove( 'wp-responsive-open' );
};
