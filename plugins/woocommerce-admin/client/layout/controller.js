/** @format */
/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';
import { parse } from 'qs';
import { find, last, isEqual } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getNewPath, getPersistedQuery, getHistory, stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Analytics from 'analytics';
import AnalyticsReport from 'analytics/report';
import AnalyticsSettings from 'analytics/settings';
import Dashboard from 'dashboard';
import DevDocs from 'devdocs';

const getPages = () => {
	const pages = [];

	if ( window.wcAdminFeatures.devdocs ) {
		pages.push( {
			container: DevDocs,
			path: '/devdocs',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		} );
		pages.push( {
			container: DevDocs,
			path: '/devdocs/:component',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		} );
	}

	if ( window.wcAdminFeatures[ 'analytics-dashboard' ] ) {
		pages.push( {
			container: Dashboard,
			path: '/',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		} );
	}

	if ( window.wcAdminFeatures.analytics ) {
		pages.push( {
			container: Analytics,
			path: '/analytics',
			wpOpenMenu: 'toplevel_page_wc-admin--analytics-revenue',
			wpClosedMenu: 'toplevel_page_woocommerce',
		} );
		pages.push( {
			container: AnalyticsSettings,
			path: '/analytics/settings',
			wpOpenMenu: 'toplevel_page_wc-admin--analytics-revenue',
			wpClosedMenu: 'toplevel_page_woocommerce',
		} );
		pages.push( {
			container: AnalyticsReport,
			path: '/analytics/:report',
			wpOpenMenu: 'toplevel_page_wc-admin--analytics-revenue',
			wpClosedMenu: 'toplevel_page_woocommerce',
		} );
	}

	return pages;
};

class Controller extends Component {
	componentDidMount() {
		window.document.documentElement.scrollTop = 0;
	}

	componentDidUpdate( prevProps ) {
		const prevQuery = this.getQuery( prevProps.location.search );
		const prevBaseQuery = this.getBaseQuery( prevProps.location.search );
		const baseQuery = this.getBaseQuery( this.props.location.search );

		if ( prevQuery.page > 1 && ! isEqual( prevBaseQuery, baseQuery ) ) {
			getHistory().replace( getNewPath( { page: 1 } ) );
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

	getBaseQuery( searchString ) {
		const query = this.getQuery( searchString );
		delete query.page;
		return query;
	}

	render() {
		// Pass URL parameters (example :report -> params.report) and query string parameters
		const { path, url, params } = this.props.match;
		const query = this.getQuery( this.props.location.search );
		const page = find( getPages(), { path } );

		if ( ! page ) {
			return null; // @todo What should we display or do when a route/page doesn't exist?
		}

		window.wpNavMenuUrlUpdate( page, query );
		window.wpNavMenuClassChange( page );
		return createElement( page.container, { params, path: url, pathMatch: path, query } );
	}
}

// Update links in wp-admin menu to persist time related queries
window.wpNavMenuUrlUpdate = function( page, query ) {
	const search = stringifyQuery( getPersistedQuery( query ) );

	Array.from(
		document.querySelectorAll( `#${ page.wpOpenMenu } a, #${ page.wpClosedMenu } a` )
	).forEach( item => {
		/**
		 * Example hrefs:
		 *
		 * http://example.com/wp-admin/admin.php?page=wc-admin#/analytics/orders?period=today&compare=previous_year
		 * http://example.com/wp-admin/admin.php?page=wc-admin#/?period=week&compare=previous_year
		 * http://example.com/wp-admin/admin.php?page=wc-admin
		 */
		if ( item.href.includes( 'wc-admin' ) && ! item.href.includes( 'devdocs' ) ) {
			const url = item.href.split( 'wc-admin' );
			const hashUrl = last( url );
			const base = hashUrl.split( '?' )[ 0 ];
			const href = `${ url[ 0 ] }wc-admin${ '#' === base[ 0 ] ? '' : '#/' }${ base }${ search }`;
			item.href = href;
		}
	} );
};

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function( page ) {
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

	const pageHash = window.location.hash.split( '?' )[ 0 ];
	const currentItemsSelector =
		pageHash === '#/'
			? `li > a[href$="${ pageHash }"], li > a[href*="${ pageHash }?"]`
			: `li > a[href*="${ pageHash }"]`;
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

	// Sometimes navigating from the subMenu to Dashboard does not close subMenu
	if ( page.wpClosedMenu ) {
		const closedMenu = document.querySelector( '#' + page.wpClosedMenu );
		closedMenu.classList.remove( 'wp-has-current-submenu' );
		closedMenu.classList.remove( 'wp-menu-open' );
		closedMenu.classList.add( 'wp-not-current-submenu' );
	}

	const wpWrap = document.querySelector( '#wpwrap' );
	wpWrap.classList.remove( 'wp-responsive-open' );
};

export { Controller, getPages };
