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
	const PRODUCTS_1 = [
		{
			id: 3,
			name: 'my-product-3',
		},
		{
			id: 4,
			name: 'my-product-2-4',
		},
	];
	const PRODUCTS_2 = [
		{
			id: 1,
			name: 'my-product-1',
		},
		{
			id: 2,
			name: 'my-product-2',
		},
	];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/products' ) {
				return Promise.resolve( PRODUCTS_1 );
			}
			if ( options.path === '/wc/v3/products?orderby=date' ) {
				return Promise.resolve( PRODUCTS_2 );
			}
		} );
	} );

	it( 'returns requested products', async () => {
		getProducts().then( data => expect( data ).toEqual( PRODUCTS_1 ) );
	} );

	it( 'returns requested products for a specific query', async () => {
		getProducts( { orderby: 'date' } ).then( data => expect( data ).toEqual( PRODUCTS_2 ) );
	} );
} );
