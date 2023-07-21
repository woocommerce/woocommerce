/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { App } from './App';

addFilter( 'woocommerce_admin_pages_list', 'woo-ai-example', ( pages ) => {
	pages.push( {
		container: App,
		path: '/woo-ai-example',
		wpOpenMenu: 'toplevel_page_woocommerce',
		capability: 'read',
		breadcrumbs: [ 'Woo AI Example' ],
		navArgs: { id: 'woo-ai-example' },
	} );

	return pages;
} );
