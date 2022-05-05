/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './index.scss';

const MyExamplePage = () => <h1>My Example Extension</h1>;

addFilter( 'woocommerce_admin_pages_list', 'my-namespace', ( pages ) => {
	pages.push( {
		container: MyExamplePage,
		path: '/example',
		breadcrumbs: [ 'My Example Page' ],
		navArgs: {
			id: 'my-example-page',
		},
	} );

	return pages;
} );
