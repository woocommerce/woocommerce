/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { App } from './App';

addFilter( 'woocommerce_admin_pages_list', 'live-branches', ( pages ) => {
	pages.push( {
		container: App,
		path: '/live-branches',
		wpOpenMenu: 'toplevel_page_woocommerce',
		capability: 'read',
		breadcrumbs: [ 'Live Branches' ],
		navArgs: { id: 'live-branches' },
	} );

	return pages;
} );
