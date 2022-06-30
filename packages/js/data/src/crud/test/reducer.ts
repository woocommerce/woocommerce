/**
 * Internal dependencies
 */
import { Actions } from '../actions';
import { createReducer, ResourceState } from '../reducer';
import { CRUD_ACTIONS } from '../crud-actions';
import { getResourceName } from '../../utils';
import { Item, ItemQuery } from '../types';
import TYPES from '../action-types';

const defaultState: ResourceState = {
	items: {},
	errors: {},
	data: {},
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
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
		};
		const update: Item = {
			id: 2,
			status: 'draft',
		};

		const state = reducer( initialState, {
			type: TYPES.GET_ITEM_SUCCESS,
			id: update.id,
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
		} );

		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );

		expect( state.items[ resourceName ].data ).toHaveLength( 2 );
		expect( state.items[ resourceName ].data.includes( 1 ) ).toBeTruthy();
		expect( state.items[ resourceName ].data.includes( 2 ) ).toBeTruthy();

		expect( state.data[ 1 ] ).toEqual( items[ 0 ] );
		expect( state.data[ 2 ] ).toEqual( items[ 1 ] );
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
		} );

		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );

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
		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEMS, query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEMS_ERROR,
			query,
			error,
			errorType: CRUD_ACTIONS.GET_ITEMS,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle GET_ITEM_ERROR', () => {
		const id = 3;
		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEM, { id } );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEM_ERROR,
			id,
			error,
			errorType: CRUD_ACTIONS.GET_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle GET_ITEM_ERROR', () => {
		const id = 3;
		const resourceName = getResourceName( CRUD_ACTIONS.GET_ITEM, { id } );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_ITEM_ERROR,
			id,
			error,
			errorType: CRUD_ACTIONS.GET_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle CREATE_ITEM_ERROR', () => {
		const query = { name: 'Invalid product' };
		const resourceName = getResourceName( CRUD_ACTIONS.CREATE_ITEM, query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.CREATE_ITEM_ERROR,
			query,
			error,
			errorType: CRUD_ACTIONS.CREATE_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle UPDATE_ITEM_ERROR', () => {
		const id = 2;
		const resourceName = getResourceName( CRUD_ACTIONS.UPDATE_ITEM, {
			id,
		} );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_ITEM_ERROR,
			id,
			error,
			errorType: CRUD_ACTIONS.UPDATE_ITEM,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle UPDATE_ITEM_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ResourceState = {
			items: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
		};
		const item: Item = {
			id: 2,
			name: 'Holy smokes!',
			status: 'draft',
		};

		const state = reducer( initialState, {
			type: TYPES.UPDATE_ITEM_SUCCESS,
			id: item.id,
			item,
		} );

		expect( state.items ).toEqual( initialState.items );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].name ).toEqual( item.name );
	} );

	it( 'should handle CREATE_ITEM_SUCCESS', () => {
		const item: Item = {
			id: 2,
			name: 'Off the hook!',
			status: 'draft',
		};

		const state = reducer( defaultState, {
			type: TYPES.CREATE_ITEM_SUCCESS,
			id: item.id,
			item,
		} );

		expect( state.data[ 2 ].name ).toEqual( item.name );
		expect( state.data[ 2 ].status ).toEqual( item.status );
	} );
} );
