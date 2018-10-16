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
import ordersReducer from '../reducer';
import { getJsonString } from 'store/utils';

describe( 'ordersReducer()', () => {
	it( 'returns an empty data object by default', () => {
		const state = ordersReducer( undefined, {} );
		expect( state ).toEqual( {} );
	} );

	it( 'returns with received orders data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'date',
		};
		const orders = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

		const state = ordersReducer( originalState, {
			type: 'SET_ORDERS',
			query,
			orders,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( orders );
	} );

	it( 'tracks multiple queries in orders data', () => {
		const otherQuery = {
			orderby: 'id',
		};
		const otherQueryKey = getJsonString( otherQuery );
		const otherOrders = [ { id: 1 }, { id: 2 }, { id: 3 } ];
		const otherQueryState = {
			[ otherQueryKey ]: otherOrders,
		};
		const originalState = deepFreeze( otherQueryState );
		const query = {
			orderby: 'date',
		};
		const orders = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

		const state = ordersReducer( originalState, {
			type: 'SET_ORDERS',
			query,
			orders,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( orders );
		expect( state[ otherQueryKey ] ).toEqual( otherOrders );
	} );

	it( 'returns with received error data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'date',
		};

		const state = ordersReducer( originalState, {
			type: 'SET_ORDERS_ERROR',
			query,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( ERROR );
	} );
} );
