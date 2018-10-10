/** @format */
/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';
import { parse } from 'qs';
import { find } from 'lodash';

/**
 * External dependencies
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
			wpMenu: 'toplevel_page_woocommerce',
		},
		{
			container: Analytics,
			path: '/analytics',
			wpMenu: 'toplevel_page_wc-admin--analytics',
		},
		{
			container: AnalyticsReport,
			path: '/analytics/:report',
			wpMenu: 'toplevel_page_wc-admin--analytics',
		},
		{
			container: DevDocs,
			path: '/devdocs',
			wpMenu: 'toplevel_page_woocommerce',
		},
		{
			container: DevDocs,
			path: '/devdocs/:component',
			wpMenu: 'toplevel_page_woocommerce',
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
		window.wpNavMenuClassChange( page.wpMenu, this.props.location.pathname );
		return createElement( page.container, { params, path: url, pathMatch: path, query } );
	}
}

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function( menuClass, pathname ) {
	const path = '/' === pathname ? '' : '#' + pathname;
	Array.from( document.getElementsByClassName( 'current' ) ).forEach( function( item ) {
		item.classList.remove( 'current' );
	} );

	const submenu = document.querySelector( '.wp-has-current-submenu' );
	submenu.classList.remove( 'wp-has-current-submenu' );
	submenu.classList.remove( 'wp-menu-open' );
	submenu.classList.remove( 'selected' );
	submenu.classList.add( 'wp-not-current-submenu' );
	submenu.classList.add( 'menu-top' );

	document
		.querySelector( `li > a[href$="admin.php?page=wc-admin${ path }"]` )
		.parentElement.classList.add( 'current' );

	const currentMenu = document.querySelector( '#' + menuClass );
	currentMenu.classList.remove( 'wp-not-current-submenu' );
	currentMenu.classList.add( 'wp-has-current-submenu' );
	currentMenu.classList.add( 'wp-menu-open' );
	currentMenu.classList.add( 'current' );
};

export { Controller, getPages };
