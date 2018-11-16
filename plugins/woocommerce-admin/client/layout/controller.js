/** @format */
/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';
import { parse } from 'qs';
import { find, last } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getTimeRelatedQuery, stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Analytics from 'analytics';
import AnalyticsReport from 'analytics/report';
import Dashboard from 'dashboard';
import DevDocs from 'devdocs';

const getPages = () => {
	const pages = [
		{
			container: Dashboard,
			path: '/',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		},
		{
			container: Analytics,
			path: '/analytics',
			wpOpenMenu: 'toplevel_page_wc-admin--analytics-revenue',
			wpClosedMenu: 'toplevel_page_woocommerce',
		},
		{
			container: AnalyticsReport,
			path: '/analytics/:report',
			wpOpenMenu: 'toplevel_page_wc-admin--analytics-revenue',
			wpClosedMenu: 'toplevel_page_woocommerce',
		},
		{
			container: DevDocs,
			path: '/devdocs',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		},
		{
			container: DevDocs,
			path: '/devdocs/:component',
			wpOpenMenu: 'toplevel_page_woocommerce',
			wpClosedMenu: 'toplevel_page_wc-admin--analytics-revenue',
		},
	];

	return pages;
};

class Controller extends Component {
	render() {
		// Pass URL parameters (example :report -> params.report) and query string parameters
		const { path, url, params } = this.props.match;
		const search = this.props.location.search.substring( 1 );
		const query = parse( search );
		const page = find( getPages(), { path } );
		window.wpNavMenuUrlUpdate( page, query );
		window.wpNavMenuClassChange( page );
		return createElement( page.container, { params, path: url, pathMatch: path, query } );
	}
}

// Update links in wp-admin menu to persist time related queries
window.wpNavMenuUrlUpdate = function( page, query ) {
	const search = stringifyQuery( getTimeRelatedQuery( query ) );

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

	const currentMenu = document.querySelector( '#' + page.wpOpenMenu );
	currentMenu.classList.remove( 'wp-not-current-submenu' );
	currentMenu.classList.add( 'wp-has-current-submenu' );
	currentMenu.classList.add( 'wp-menu-open' );
	currentMenu.classList.add( 'current' );

	// Sometimes navigating from the subMenu to Dashboard does not close subMenu
	const closedMenu = document.querySelector( '#' + page.wpClosedMenu );
	closedMenu.classList.remove( 'wp-has-current-submenu' );
	closedMenu.classList.remove( 'wp-menu-open' );
	closedMenu.classList.add( 'wp-not-current-submenu' );
};

export { Controller, getPages };
