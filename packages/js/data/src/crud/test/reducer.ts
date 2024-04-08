/**
 * Internal dependencies
 */
import { Actions } from '../actions';
import { createReducer, ResourceState } from '../reducer';
import { CRUD_ACTIONS } from '../crud-actions';
import { getResourceName } from '../../utils';
import { getRequestIdentifier } from '..//utils';
import { Item, ItemQuery } from '../types';
import TYPES from '../action-types';

const defaultState: ResourceState = {
	items: {},
	errors: {},
	itemsCount: {},
	data: {},
	requesting: {},
};

const reducer = createReducer();

describe( 'crud reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} as Actions );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle GET_ITEM_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ResourceState = {
			items: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			itemsCount: {},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			requesting: {},
		};
		const update: Item = {
			id: 2,
			status: 'draft',
		};

		const state = reducer( initialState, {
			type: TYPES.GET_ITEM_SUCCESS,
			key: update.id,
			item: update,
		} );

		expect( state.items ).toEqual( initialState.items );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].status ).toEqual( update.status );
	} );

	it( 'should handle GET_ITEMS_SUCCESS', () => {
		const items: Item[] = [
			{ id: 1, name: 'Yum!' },
			{ id: 2, name: 'Dynamite!' },
		];
		const query: Partial< ItemQuery > = { status: 'draft' };
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_SUCCESS,
			items,
			query,
			urlParameters: [],
		} );

		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS,
			query
		);

		expect( state.items[ resourceName ].data ).toHaveLength( 2 );
		expect( state.items[ resourceName ].data.includes( 1 ) ).toBeTruthy();
		expect( state.items[ resourceName ].data.includes( 2 ) ).toBeTruthy();

		expect( state.data[ 1 ] ).toEqual( items[ 0 ] );
		expect( state.data[ 2 ] ).toEqual( items[ 1 ] );
	} );

	it( 'should handle GET_ITEMS_TOTAL_COUNT_SUCCESS', () => {
		const query: Partial< ItemQuery > = { status: 'draft' };
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_TOTAL_COUNT_SUCCESS,
			query,
			totalCount: 10,
		} );

		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );

		expect( state.itemsCount[ resourceName ] ).toEqual( 10 );
	} );

	it( 'should handle GET_ITEMS_SUCCESS with urlParameters', () => {
		const items: Item[] = [
			{ id: 1, name: 'Yum!' },
			{ id: 2, name: 'Dynamite!' },
		];
		const query: Partial< ItemQuery > = { status: 'draft' };
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_SUCCESS,
			items,
			query,
			urlParameters: [ 5 ],
		} );

		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS,
			query
		);

		expect( state.items[ resourceName ].data ).toHaveLength( 2 );
		expect( state.data[ '5/1' ] ).toEqual( items[ 0 ] );
		expect( state.data[ '5/2' ] ).toEqual( items[ 1 ] );
	} );

	it( 'GET_ITEMS_SUCCESS should not remove previously added fields, only update new ones', () => {
		const initialState: ResourceState = {
			...defaultState,
			data: {
				1: { id: 1, name: 'Yum!', price: '10.00', description: 'test' },
				2: {
					id: 2,
					name: 'Dynamite!',
					price: '10.00',
					description: 'dynamite',
				},
			},
		};

		const items: Item[] = [
			{ id: 1, name: 'Yum! Updated' },
			{ id: 2, name: 'Dynamite!' },
		];
		const query: Partial< ItemQuery > = { status: 'draft' };
		const state = reducer( initialState, {
			type: TYPES.GET_ITEMS_SUCCESS,
			items,
			query,
			urlParameters: [],
		} );

		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS,
			query
		);

		expect( state.items[ resourceName ].data ).toHaveLength( 2 );
		expect( state.items[ resourceName ].data.includes( 1 ) ).toBeTruthy();
		expect( state.items[ resourceName ].data.includes( 2 ) ).toBeTruthy();

		expect( state.data[ 1 ].name ).toEqual( items[ 0 ].name );
		expect( state.data[ 1 ].price ).toEqual( initialState.data[ 1 ].price );
		expect( state.data[ 1 ].description ).toEqual(
			initialState.data[ 1 ].description
		);
		expect( state.data[ 2 ] ).toEqual( initialState.data[ 2 ] );
	} );

	it( 'should handle GET_ITEMS_ERROR', () => {
		const query: Partial< ItemQuery > = { status: 'draft' };
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS,
			query
		);
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_ERROR,
			query,
			error,
			errorType: CRUD_ACTIONS.GET_ITEMS,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle GET_ITEMS_TOTAL_COUNT_ERROR', () => {
		const query: Partial< ItemQuery > = { status: 'draft' };
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS_TOTAL_COUNT,
			query
		);
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_TOTAL_COUNT_ERROR,
			query,
			error,
			errorType: CRUD_ACTIONS.GET_ITEMS_TOTAL_COUNT,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle GET_ITEM_ERROR', () => {
		const key = 3;
		const resourceName = getRequestIdentifier( CRUD_ACTIONS.GET_ITEM, key );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEM_ERROR,
			key,
			error,
			errorType: CRUD_ACTIONS.GET_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle GET_ITEM_ERROR', () => {
		const key = 3;
		const resourceName = getRequestIdentifier( CRUD_ACTIONS.GET_ITEM, key );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEM_ERROR,
			key,
			error,
			errorType: CRUD_ACTIONS.GET_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle CREATE_ITEM_ERROR', () => {
		const query = { name: 'Invalid product' };
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.CREATE_ITEM,
			query
		);
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.CREATE_ITEM_ERROR,
			query,
			error,
			errorType: CRUD_ACTIONS.CREATE_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
		expect( state.requesting[ resourceName ] ).toBe( false );
	} );

	it( 'should handle UPDATE_ITEM_ERROR', () => {
		const key = 2;
		const query = { property: 'value' };
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.UPDATE_ITEM,
			key,
			query
		);
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_ITEM_ERROR,
			key,
			error,
			errorType: CRUD_ACTIONS.UPDATE_ITEM,
			query,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
		expect( state.requesting[ resourceName ] ).toBe( false );
	} );

	it( 'should handle UPDATE_ITEM_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ResourceState = {
			items: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			itemsCount: {},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			requesting: {},
		};
		const item: Item = {
			id: 2,
			name: 'Holy smokes!',
			status: 'draft',
		};
		const query = { property: 'value' };
		const requestId = getRequestIdentifier(
			CRUD_ACTIONS.UPDATE_ITEM,
			item.id,
			query
		);

		const state = reducer( initialState, {
			type: TYPES.UPDATE_ITEM_SUCCESS,
			key: item.id,
			item,
			query,
		} );

		expect( state.items ).toEqual( initialState.items );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].name ).toEqual( item.name );
		expect( state.requesting[ requestId ] ).toEqual( false );
	} );

	describe( 'should handle CREATE_ITEM_SUCCESS', () => {
		it( 'when no options are passed', () => {
			const item: Item = {
				id: 2,
				name: 'Off the hook!',
				status: 'draft',
			};
			const query = {
				name: 'Off the hook!',
				status: 'draft',
			};

			const options = {
				optimisticQueryUpdate: false,
			};

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);

			const state = reducer( defaultState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data[ 2 ].name ).toEqual( item.name );
			expect( state.data[ 2 ].status ).toEqual( item.status );
			expect( state.requesting[ resourceName ] ).toEqual( false );
		} );

		it( 'when optimisticQueryUpdate is {}', () => {
			const item: Item = {
				id: 7,
				name: 'Off the hook!',
				status: 'draft',
			};
			const query = {
				name: 'Off the hook!',
				status: 'draft',
			};

			const options = {
				optimisticQueryUpdate: {},
			};

			const state = reducer( defaultState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			const itemQuery = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const itemsCountQuery = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			expect( state.data[ 7 ].name ).toEqual( item.name );
			expect( state.data[ 7 ].status ).toEqual( item.status );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);
			expect( state.requesting[ resourceName ] ).toEqual( false );

			// Items
			expect( state.items[ itemQuery ].data ).toHaveLength( 1 );
			expect( state.items[ itemQuery ].data[ 0 ] ).toEqual( item.id );

			// Items Count
			expect( state.itemsCount[ itemsCountQuery ] ).toEqual( 1 );
		} );
	} );

	it( 'should handle DELETE_ITEM_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ResourceState = {
			items: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			itemsCount: {},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			requesting: {},
		};
		const item1Updated: Item = {
			id: 1,
			status: 'pending',
		};
		const item2Updated: Item = {
			id: 2,
			status: 'trash',
		};

		let state = reducer( initialState, {
			type: TYPES.DELETE_ITEM_SUCCESS,
			key: item1Updated.id,
			item: item1Updated,
			force: true,
		} );
		state = reducer( state, {
			type: TYPES.DELETE_ITEM_SUCCESS,
			key: item2Updated.id,
			item: item2Updated,
			force: false,
		} );
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.DELETE_ITEM,
			item1Updated.id,
			true
		);

		expect( state.errors ).toEqual( initialState.errors );
		expect( state.data[ 1 ] ).toEqual( undefined );
		expect( state.data[ 2 ].status ).toEqual( 'trash' );
		expect( state.requesting[ resourceName ] ).toBe( false );
	} );

	it( 'should handle DELETE_ITEM_ERROR', () => {
		const key = 2;
		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.DELETE_ITEM,
			key,
			false
		);
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.DELETE_ITEM_ERROR,
			key,
			error,
			errorType: CRUD_ACTIONS.DELETE_ITEM,
			force: false,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
		expect( state.requesting[ resourceName ] ).toBe( false );
	} );
} );
