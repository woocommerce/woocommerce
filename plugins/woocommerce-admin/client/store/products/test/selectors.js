/*
 * @format
 */

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import selectors from '../selectors';
import { getJsonString } from 'store/utils';

const { getProducts, isGetProductsRequesting, isGetProductsError } = selectors;
jest.mock( '@wordpress/data', () => ( {
	...require.requireActual( '@wordpress/data' ),
	select: jest.fn().mockReturnValue( {} ),
} ) );

const query = { orderby: 'date' };
const queryKey = getJsonString( query );

describe( 'getProducts', () => {
	it( 'returns an empty array when no query matches values in state', () => {
		const state = deepFreeze( {} );
		expect( getProducts( state, query ) ).toEqual( [] );
	} );

	it( 'returns products for a given query', () => {
		const products = [
			{
				id: 1,
				name: 'my-product',
			},
		];
		const state = deepFreeze( {
			products: {
				[ queryKey ]: products,
			},
		} );
		expect( getProducts( state, query ) ).toEqual( products );
	} );
} );

describe( 'isGetProductsRequesting', () => {
	beforeAll( () => {
		select( 'core/data' ).isResolving = jest.fn().mockReturnValue( false );
	} );

	afterAll( () => {
		select( 'core/data' ).isResolving.mockRestore();
	} );

	function setIsResolving( isResolving ) {
		select( 'core/data' ).isResolving.mockImplementation(
			( reducerKey, selectorName ) =>
				isResolving && reducerKey === 'wc-admin' && selectorName === 'getProducts'
		);
	}

	it( 'returns false if never requested', () => {
		const result = isGetProductsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns false if request finished', () => {
		setIsResolving( false );
		const result = isGetProductsRequesting( query );
		expect( result ).toBe( false );
	} );

	it( 'returns true if requesting', () => {
		setIsResolving( true );
		const result = isGetProductsRequesting( query );
		expect( result ).toBe( true );
	} );
} );

describe( 'isGetProductsError', () => {
	it( 'returns false by default', () => {
		const state = deepFreeze( {} );
		expect( isGetProductsError( state, query ) ).toEqual( false );
	} );
	it( 'returns true if ERROR constant is found', () => {
		const state = deepFreeze( {
			products: {
				[ queryKey ]: ERROR,
			},
		} );
		expect( isGetProductsError( state, query ) ).toEqual( true );
	} );
} );
