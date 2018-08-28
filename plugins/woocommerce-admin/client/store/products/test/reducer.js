/**
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
import productsReducer, { DEFAULT_STATE } from '../reducer';
import { getJsonString } from 'store/util';

describe( 'productsReducer', () => {
	it( 'returns default state by default', () => {
		const state = productsReducer( undefined, {} );
		expect( state ).toEqual( DEFAULT_STATE );
	} );

	it( 'returns received product data', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query = {
			page: 3,
			per_page: 5,
		};
		const products = [
			{
				id: 1,
				name: 'my-product',
			},
		];
		const state = productsReducer( originalState, {
			type: 'SET_PRODUCTS',
			query,
			products,
		} );
		const queryKey = getJsonString( query );
		expect( state.queries[ queryKey ] ).toEqual( products );
	} );

	it( 'returns received product data for multiple queries', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query1 = {
			page: 3,
			per_page: 5,
		};
		const products1 = [
			{
				id: 1,
				name: 'my-product',
			},
		];
		const intermediateState = productsReducer( originalState, {
			type: 'SET_PRODUCTS',
			query: query1,
			products: products1,
		} );
		const query2 = {
			page: 6232,
			per_page: 978,
		};
		const products2 = [
			{
				id: 2,
				name: 'my-other-product',
			},
		];
		const finalState = productsReducer( intermediateState, {
			type: 'SET_PRODUCTS',
			query: query2,
			products: products2,
		} );

		const queryKey1 = getJsonString( query1 );
		const queryKey2 = getJsonString( query2 );
		expect( finalState.queries[ queryKey1 ] ).toEqual( products1 );
		expect( finalState.queries[ queryKey2 ] ).toEqual( products2 );
	} );

	it( 'returns error appropriately', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query = {
			page: 4,
			per_page: 5,
		};
		const state = productsReducer( originalState, {
			type: 'SET_PRODUCTS_ERROR',
			query,
		} );
		const queryKey = getJsonString( query );
		expect( state.queries[ queryKey ] ).toEqual( ERROR );
	} );
} );
