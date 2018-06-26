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

// TODO Remove hasOpenSideBar, as the activity panel will always be closed by default
const getPages = () => {
	const pages = [
		{
			container: Dashboard,
			path: '/',
			wpMenu: 'toplevel_page_woocommerce',
			hasOpenSidebar: true,
		},
		{
			container: Analytics,
			path: '/analytics',
			wpMenu: 'toplevel_page_woodash--analytics',
			hasOpenSidebar: false,
		},
		{
			container: AnalyticsReport,
			path: '/analytics/:report',
			wpMenu: 'toplevel_page_woodash--analytics',
			hasOpenSidebar: false,
		},
	];

	return pages;
};

class Controller extends Component {
	render() {
		// Pass URL parameters (example :report -> params.report) and query string parameters
		const { path, params } = this.props.match;
		const search = this.props.location.search.substring( 1 );
		const query = parse( search );
		const page = find( getPages(), { path } );
		window.wpNavMenuClassChange( page.wpMenu );
		return createElement( page.container, { params, path, query } );
	}
}

// When the route changes, we need to update wp-admin's menu with the correct section & current link
window.wpNavMenuClassChange = function( menuClass ) {
	jQuery( '.current' ).each( function( i, obj ) {
		jQuery( obj ).removeClass( 'current' );
	} );
	jQuery( '.wp-has-current-submenu' )
		.removeClass( 'wp-has-current-submenu' )
		.removeClass( 'wp-menu-open' )
		.removeClass( 'selected' )
		.addClass( 'wp-not-current-submenu menu-top' );
	jQuery( 'li > a[href$="admin.php?page=woodash' + window.location.hash + '"]' )
		.parent()
		.addClass( 'current' );
	jQuery( '#' + menuClass )
		.removeClass( 'wp-not-current-submenu' )
		.addClass( 'wp-has-current-submenu wp-menu-open current' );
};

export { Controller, getPages };
