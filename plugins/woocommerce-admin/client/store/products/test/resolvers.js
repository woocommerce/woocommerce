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

const { getProducts } = resolvers;

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getProducts', () => {
	const products = [
		{
			id: 1,
			name: 'my-product',
		},
	];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/products?search=abc' ) {
				return Promise.resolve( products );
			}
		} );
	} );

	it( 'returns requested products', async () => {
		getProducts( {}, { search: 'abc' } ).then( data => expect( data ).toEqual( products ) );
	} );
} );
