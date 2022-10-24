/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

const DummyApp = () => <div>Hello World, Insert App Content Here.</div>

addFilter( 'woocommerce_admin_pages_list', 'live-branches', ( pages ) => {
	pages.push( {
		container: DummyApp,
		path: '/live-branches',
		wpOpenMenu: 'toplevel_page_woocommerce',
		capability: 'read',
		breadcrumbs: [ 'Live Branches' ],
		navArgs: { id: 'live-branches' },
	} );

	console.log(pages);

	return pages;
} );
