/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getOrders } = resolvers;

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getOrders', () => {
	const ORDERS_1 = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

	const ORDERS_2 = [ { id: 1 }, { id: 2 }, { id: 3 } ];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/orders' ) {
				return Promise.resolve( ORDERS_1 );
			}
			if ( options.path === '/wc/v3/orders&orderby=id' ) {
				return Promise.resolve( ORDERS_2 );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		getOrders().then( data => expect( data ).toEqual( ORDERS_1 ) );
	} );

	it( 'returns requested report data for a specific query', async () => {
		getOrders( { orderby: 'id' } ).then( data => expect( data ).toEqual( ORDERS_2 ) );
	} );
} );
