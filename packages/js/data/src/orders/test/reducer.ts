/**
 * Internal dependencies
 */
import reducer, { OrderState } from '../reducer';
import TYPES from '../action-types';
import { getOrderResourceName, getTotalOrderCountResourceName } from '../utils';
import { Actions } from '../actions';
import { PartialOrder, OrderQuery } from '../types';

const defaultState: OrderState = {
	orders: {},
	ordersCount: {},
	errors: {},
	data: {},
};

describe( 'orders reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} as Actions );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_order', () => {
		const itemType = 'guyisms';
		const initialState: OrderState = {
			orders: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			ordersCount: {
				'total-guyisms:{}': 2,
			},
			errors: {},
			data: {
				1: { id: 1, status: 'pending' },
				2: { id: 2, status: 'completed' },
			},
		};
		const update: PartialOrder = {
			id: 2,
			status: 'completed',
		};

		const state = reducer( initialState, {
			type: TYPES.SET_ORDER,
			id: update.id,
			order: update,
		} );

		expect( state.orders ).toEqual( initialState.orders );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].status ).toEqual( update.status );
	} );

	it( 'should handle SET_orderS', () => {
		const orders: PartialOrder[] = [ { id: 1 }, { id: 2 } ];
		const totalCount = 45;
		const query: Partial< OrderQuery > = { status: 'completed' };
		const state = reducer( defaultState, {
			type: TYPES.SET_ORDERS,
			orders,
			query,
			totalCount,
		} );

		const resourceName = getOrderResourceName( query );

		expect( state.orders[ resourceName ].data ).toHaveLength( 2 );
		expect( state.orders[ resourceName ].data.includes( 1 ) ).toBeTruthy();
		expect( state.orders[ resourceName ].data.includes( 2 ) ).toBeTruthy();

		expect( state.data[ 1 ] ).toBe( orders[ 0 ] );
		expect( state.data[ 2 ] ).toBe( orders[ 1 ] );
	} );

	it( 'should handle SET_orderS_TOTAL_COUNT', () => {
		const initialQuery: Partial< OrderQuery > = {
			status: 'completed',
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
		};
		const resourceName = getTotalOrderCountResourceName( initialQuery );
		const initialState: OrderState = {
			...defaultState,
			ordersCount: {
				[ resourceName ]: 1,
			},
		};

		// Additional coverage for getTotalCountResourceName().
		const similarQueryForTotals: Partial< OrderQuery > = {
			status: 'completed',
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status' ],
		};

		const state = reducer( initialState, {
			type: TYPES.SET_ORDERS_TOTAL_COUNT,
			query: similarQueryForTotals,
			totalCount: 2,
		} );

		expect( state.ordersCount ).toEqual( {
			[ resourceName ]: 2,
		} );
	} );

	it( 'should handle SET_ERROR', () => {
		const query: Partial< OrderQuery > = { status: 'pending' };
		const resourceName = getOrderResourceName( query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			query,
			error,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );
} );
