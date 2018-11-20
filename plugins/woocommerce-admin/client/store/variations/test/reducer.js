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
import variationsReducer, { DEFAULT_STATE } from '../reducer';
import { getJsonString } from 'store/utils';

describe( 'variationsReducer', () => {
	it( 'returns default state by default', () => {
		const state = variationsReducer( undefined, {} );
		expect( state ).toEqual( DEFAULT_STATE );
	} );

	it( 'returns received variations data', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query = {
			page: 3,
			per_page: 5,
		};
		const variations = [
			{
				id: 1,
				attributes: [],
			},
		];
		const state = variationsReducer( originalState, {
			type: 'SET_VARIATIONS',
			query,
			variations,
		} );
		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( variations );
	} );

	it( 'returns received variations data for multiple queries', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query1 = {
			page: 3,
			per_page: 5,
		};
		const variations1 = [
			{
				id: 1,
				attributes: [],
			},
		];
		const intermediateState = variationsReducer( originalState, {
			type: 'SET_VARIATIONS',
			query: query1,
			variations: variations1,
		} );
		const query2 = {
			page: 6232,
			per_page: 978,
		};
		const variations2 = [
			{
				id: 2,
				name: 'my-other-product',
			},
		];
		const finalState = variationsReducer( intermediateState, {
			type: 'SET_VARIATIONS',
			query: query2,
			variations: variations2,
		} );

		const queryKey1 = getJsonString( query1 );
		const queryKey2 = getJsonString( query2 );
		expect( finalState[ queryKey1 ] ).toEqual( variations1 );
		expect( finalState[ queryKey2 ] ).toEqual( variations2 );
	} );

	it( 'returns error appropriately', () => {
		const originalState = deepFreeze( { ...DEFAULT_STATE } );
		const query = {
			page: 4,
			per_page: 5,
		};
		const state = variationsReducer( originalState, {
			type: 'SET_VARIATIONS_ERROR',
			query,
		} );
		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( ERROR );
	} );
} );
