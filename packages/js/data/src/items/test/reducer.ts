/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';
import { getResourceName } from '../../utils';
import { getTotalCountResourceName } from '../utils';
import { ProductItem } from '../types';

const defaultState = {
	items: {},
	errors: {},
	data: {},
};

describe( 'items reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error - we're testing the default state
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_ITEM', () => {
		const itemType = 'products';
		const initialState = {
			items: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
				'total-guyisms:{}': 2,
			},
			errors: {},
			data: {
				[ itemType ]: {
					1: { id: 1, name: 'Donkey', tax_status: 'flavortown' },
					2: { id: 2, name: 'Sauce', tax_status: 'flavortown' },
				},
			},
		};
		const update = {
			id: 2,
			tax_status: 'bomb dot com',
		};

		const state = reducer( initialState, {
			type: TYPES.SET_ITEM,
			id: update.id,
			item: update,
			itemType,
		} );

		expect( state.items ).toEqual( initialState.items );
		expect( state.errors ).toEqual( initialState.errors );

		const item = state.data[ itemType ] || {};

		expect( item[ '1' ] ).toEqual( initialState.data[ itemType ][ '1' ] );
		expect( item[ '2' ].id ).toEqual(
			initialState.data[ itemType ][ '2' ].id
		);
		expect( ( item[ '2' ] as ProductItem ).name ).toEqual(
			initialState.data[ itemType ][ '2' ].name
		);
		expect( ( item[ '2' ] as Partial< ProductItem > ).tax_status ).toEqual(
			update.tax_status
		);
	} );

	it( 'should handle SET_ITEMS', () => {
		const items = [
			{ id: 1, name: 'Yum!' },
			{ id: 2, name: 'Dynamite!' },
		];
		const totalCount = 45;
		const query = { page: 1 };
		const itemType = 'products';
		const state = reducer( defaultState, {
			type: TYPES.SET_ITEMS,
			items,
			itemType,
			query,
			totalCount,
		} );

		const resourceName = getResourceName( itemType, query );

		expect(
			( state.items[ resourceName ] as { [ key: string ]: number[] } )
				.data
		).toHaveLength( 2 );
		expect(
			(
				state.items[ resourceName ] as {
					[ key: string ]: number[];
				}
			 ).data.includes( 1 )
		).toBeTruthy();
		expect(
			(
				state.items[ resourceName ] as {
					[ key: string ]: number[];
				}
			 ).data.includes( 2 )
		).toBeTruthy();

		expect( ( state.data[ itemType ] || {} )[ '1' ] ).toBe( items[ 0 ] );
		expect( ( state.data[ itemType ] || {} )[ '2' ] ).toBe( items[ 1 ] );
	} );

	it( 'should handle SET_ITEMS_TOTAL_COUNT', () => {
		const itemType = 'products';
		const initialQuery = {
			status: 'flavortown',
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
		};
		const resourceName = getTotalCountResourceName(
			itemType,
			initialQuery
		);
		const initialState = {
			items: {
				[ resourceName ]: 1,
			},
			data: {},
			errors: {},
		};

		// Additional coverage for getTotalCountResourceName().
		const similarQueryForTotals = {
			status: 'flavortown',
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'name', 'status' ],
		};

		const state = reducer( initialState, {
			type: TYPES.SET_ITEMS_TOTAL_COUNT,
			itemType,
			query: similarQueryForTotals,
			totalCount: 2,
		} );

		expect( state ).toEqual( {
			data: {},
			errors: {},
			items: {
				[ resourceName ]: 2,
			},
		} );
	} );

	it( 'should handle SET_ERROR', () => {
		const query = { status: 'flavortown' };
		const itemType = 'products';
		const resourceName = getResourceName( itemType, query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			itemType,
			query,
			error,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );
} );
