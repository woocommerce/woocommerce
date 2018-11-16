/*
 * @format
 */

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getProducts } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setProducts: jest.fn(),
	} ),
} ) );
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
			if ( options.path === '/wc/v3/reports/products' ) {
				return Promise.resolve( PRODUCTS_1 );
			}
			if ( options.path === '/wc/v3/reports/products?orderby=date' ) {
				return Promise.resolve( PRODUCTS_2 );
			}
		} );
	} );

	it( 'returns requested products', async () => {
		expect.assertions( 1 );
		await getProducts();
		expect( dispatch().setProducts ).toHaveBeenCalledWith( PRODUCTS_1, undefined );
	} );

	it( 'returns requested products for a specific query', async () => {
		expect.assertions( 1 );
		await getProducts( { orderby: 'date' } );
		expect( dispatch().setProducts ).toHaveBeenCalledWith( PRODUCTS_2, { orderby: 'date' } );
	} );
} );
