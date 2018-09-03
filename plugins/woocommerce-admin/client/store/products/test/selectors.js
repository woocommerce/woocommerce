/*
 * @format
 */

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import selectors from '../selectors';
import { select } from '@wordpress/data';
import { getJsonString } from 'store/utils';

const { getProducts, isProductsRequesting, isProductsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

describe( 'getProducts', () => {
	it( 'returns undefined when no query matches values in state', () => {
		const state = deepFreeze( {} );
		expect( getProducts( state, { search: 'abc' } ) ).toEqual( undefined );
	} );

	it( 'returns products for a given query', () => {
		const products = [
			{
				id: 1,
				name: 'my-product',
			},
		];
		const query = { search: 'abc' };
		const queryKey = getJsonString( query );
		const state = deepFreeze( {
			products: {
				queries: {
					[ queryKey ]: products,
				},
			},
		} );
		expect( getProducts( state, query ) ).toEqual( products );
	} );
} );

describe( 'isProductsRequesting', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	const query = { search: 'abc' };

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getProducts'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isProductsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isProductsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isProductsRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isProductsError', () => {
	const query = { search: 'abc' };

	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isProductsError( state, query ) ).toEqual( false );
	} );
	it( 'returns true if ERROR constant is found', () => {
		const queryKey = getJsonString( query );
		const state = deepFreeze( {
			products: {
				queries: {
					[ queryKey ]: ERROR,
				},
			},
		} );
		expect( isProductsError( state, query ) ).toEqual( true );
	} );
} );
