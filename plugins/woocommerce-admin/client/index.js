/** @format */
/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { APIProvider } from '@wordpress/components';
import { Component, createElement, render } from '@wordpress/element';
import { HashRouter as Router, Route, Switch } from 'react-router-dom';
import { parse } from 'qs';
import { pick, find } from 'lodash';

/**
 * Internal dependencies
 */
import Analytics from './analytics';
import AnalyticsReport from './analytics/report';
import Dashboard from './dashboard';

const getPages = () => {
	const pages = [
		{
			container: Dashboard,
			path: '/',
			wpMenu: 'toplevel_page_woodash',
		},
		{
			container: Analytics,
			path: '/analytics',
			wpMenu: 'toplevel_page_woodash--analytics',
		},
		{
			container: AnalyticsReport,
			path: '/analytics/:report',
			wpMenu: 'toplevel_page_woodash--analytics',
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
		return createElement( page.container, { params, query } );
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

render(
	<APIProvider
		{ ...wpApiSettings }
		{ ...pick( wp.api, [ 'postTypeRestBaseMapping', 'taxonomyRestBaseMapping' ] ) }
	>
		<Router>
			<Switch>
				{ getPages().map( page => {
					return <Route path={ page.path } exact component={ Controller } />;
				} ) }
				<Route component={ Controller } />
			</Switch>
		</Router>
	</APIProvider>,
	document.getElementById( 'root' )
);

function editText( string ) {
	return `Filtered: ${ string }`;
}
addFilter( 'woodash.example', 'editText', editText );
