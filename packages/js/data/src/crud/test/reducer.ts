/**
 * Internal dependencies
 */
import { Actions } from '../actions';
import { createReducer, ResourceState } from '../reducer';
import { CRUD_ACTIONS } from '../crud-actions';
import { getResourceName, getTotalCountResourceName } from '../../utils';
import { getRequestIdentifier } from '../utils';
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
			urlParameters: [ 100, 5 ],
		} );

		const resourceName = getRequestIdentifier(
			CRUD_ACTIONS.GET_ITEMS,
			query
		);

		expect( state.items[ resourceName ].data ).toHaveLength( 2 );
		expect( state.data[ '100/5/1' ] ).toEqual( items[ 0 ] );
		expect( state.data[ '100/5/2' ] ).toEqual( items[ 1 ] );
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
		it( 'with empty previous state', () => {
			const item: Item = {
				id: 2,
				name: 'Off the hook!',
				status: 'draft',
			};
			const query = {
				name: 'Off the hook!',
				status: 'draft',
			};

			const state = reducer( defaultState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options: {},
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);

			expect( state.data[ 2 ].name ).toEqual( item.name );
			expect( state.data[ 2 ].status ).toEqual( item.status );
			expect( state.requesting[ resourceName ] ).toEqual( false );

			// Not optimitic query update
			expect( state.items ).toEqual( {} );
			expect( state.itemsCount ).toEqual( {} );
		} );

		it( 'with previous state', () => {
			const item: Item = {
				id: 3,
				name: 'banana',
				status: 'draft',
			};

			const query = {
				name: 'banana',
				status: 'draft',
			};

			const queryId = { type: 'fruit' };

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				queryId
			);

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				queryId
			);

			const initialState: ResourceState = {
				items: {
					[ getItemsQueryId ]: {
						data: [ 1, 2 ],
					},
				},
				itemsCount: {
					[ getItemsCountQueryId ]: 2,
				},
				errors: {},
				data: {
					1: { id: 1, name: 'apple', status: 'draft' },
					2: { id: 2, name: 'pine', status: 'publish' },
				},
				requesting: {},
			};

			const state = reducer( initialState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options: {},
			} );

			expect( state.data ).toEqual( {
				1: { id: 1, name: 'apple', status: 'draft' },
				2: { id: 2, name: 'pine', status: 'publish' },
				3: { id: 3, name: 'banana', status: 'draft' },
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);
			expect( state.requesting[ resourceName ] ).toEqual( false );

			// Not optimitic query update
			expect( state.items[ getItemsQueryId ].data ).toHaveLength( 2 );
			expect( state.items[ getItemsQueryId ].data ).toEqual( [ 1, 2 ] );
			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 2 );
		} );

		it( 'with empty previous state, and optimisticQueryUpdate options', () => {
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
				optimisticQueryUpdate: { order_by: 'name' },
			};

			const state = reducer( defaultState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				7: {
					id: 7,
					name: 'Off the hook!',
					status: 'draft',
				},
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);
			expect( state.requesting[ resourceName ] ).toEqual( false );

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			expect( state.items[ getItemsQueryId ].data ).toHaveLength( 1 );
			expect( state.items[ getItemsQueryId ].data[ 0 ] ).toEqual( 7 ); // Item id

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 1 );
		} );

		it( 'with previous state, and optimisticQueryUpdate options', () => {
			const item: Item = {
				id: 7,
				name: 'kiwi',
				status: 'draft',
			};

			const query = {
				name: 'kiwi',
				status: 'draft',
			};

			const options = {
				optimisticQueryUpdate: { random: 'fruit' },
			};

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const initialState: ResourceState = {
				items: {
					[ getItemsQueryId ]: {
						data: [ 1, 2 ],
					},
				},
				itemsCount: {
					[ getItemsCountQueryId ]: 2,
				},
				errors: {},
				data: {
					1: { id: 1, name: 'apple', status: 'draft' },
					2: { id: 2, name: 'pine', status: 'publish' },
				},
				requesting: {},
			};

			const state = reducer( initialState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				1: { id: 1, name: 'apple', status: 'draft' },
				2: { id: 2, name: 'pine', status: 'publish' },
				7: { id: 7, name: 'kiwi', status: 'draft' },
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);
			expect( state.requesting[ resourceName ] ).toEqual( false );

			expect( state.items[ getItemsQueryId ].data ).toHaveLength( 3 );
			expect( state.items[ getItemsQueryId ].data ).toEqual( [
				1, 2, 7,
			] );

			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 3 );
		} );

		it( `with previous state,
	 and optimisticQueryUpdate options with order_by: name`, () => {
			const item: Item = {
				id: 7,
				name: 'kiwi',
				status: 'draft',
			};

			const query = {
				name: 'kiwi',
				status: 'draft',
			};

			const options = {
				optimisticQueryUpdate: { order_by: 'name' },
			};

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const initialState: ResourceState = {
				items: {
					[ getItemsQueryId ]: {
						data: [ 1, 2 ],
					},
				},
				itemsCount: {
					[ getItemsCountQueryId ]: 2,
				},
				errors: {},
				data: {
					1: { id: 1, name: 'apple', status: 'draft' },
					2: { id: 2, name: 'pine', status: 'publish' },
				},
				requesting: {},
			};

			const state = reducer( initialState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				1: { id: 1, name: 'apple', status: 'draft' },
				2: { id: 2, name: 'pine', status: 'publish' },
				7: { id: 7, name: 'kiwi', status: 'draft' },
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				item.id,
				query
			);
			expect( state.requesting[ resourceName ] ).toEqual( false );

			expect( state.items[ getItemsQueryId ].data ).toEqual( [
				// order by name: apple(1), kiwi(7), pine(2)
				1, 7, 2,
			] );
		} );

		it( `with empty previous state,
	 and optimisticQueryUpdate and
	 optimisticUrlParameters options`, () => {
			const item: Item = {
				id: 7,
				name: 'Off the hook!',
				status: 'draft',
			};

			const query = {
				name: 'Off the hook!',
				status: 'draft',
				parent_id: 200,
			};

			const options = {
				optimisticQueryUpdate: { parent_id: 200 },
				optimisticUrlParameters: [ 200 ],
			};

			const state = reducer( defaultState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				'200/7': {
					id: 7,
					name: 'Off the hook!',
					status: 'draft',
				},
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				'200/7',
				query
			);

			expect( state.requesting[ resourceName ] ).toEqual( false );

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			expect( state.items[ getItemsQueryId ] ).toBeDefined();
			expect( state.items[ getItemsQueryId ].data ).toEqual( [
				'200/7',
			] );

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 1 );
		} );

		it( `with previous state,
	 and optimisticQueryUpdate with order_by: name,
	 and optimisticUrlParameters options`, () => {
			const item: Item = {
				id: 7,
				name: 'kiwi',
				status: 'draft',
			};

			const query = {
				name: 'kiwi',
				status: 'draft',
				parent_id: 200,
			};

			const options = {
				optimisticQueryUpdate: { parent_id: 200, order_by: 'name' },
				optimisticUrlParameters: [ 200 ],
			};

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const initialState: ResourceState = {
				items: {
					[ getItemsQueryId ]: {
						data: [ '200/1', '200/2' ],
					},
				},
				itemsCount: {
					[ getItemsCountQueryId ]: 2,
				},
				errors: {},
				data: {
					'200/1': { id: 1, name: 'apple', status: 'draft' },
					'200/2': { id: 2, name: 'pine', status: 'publish' },
				},
				requesting: {},
			};

			const state = reducer( initialState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				'200/1': {
					id: 1,
					name: 'apple',
					status: 'draft',
				},
				'200/2': {
					id: 2,
					name: 'pine',
					status: 'publish',
				},
				'200/7': {
					id: 7,
					name: 'kiwi',
					status: 'draft',
				},
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				'200/7',
				query
			);

			expect( state.requesting[ resourceName ] ).toEqual( false );

			expect( state.items[ getItemsQueryId ] ).toBeDefined();
			expect( state.items[ getItemsQueryId ].data ).toEqual( [
				'200/1',
				'200/7',
				'200/2',
			] );

			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 3 );
		} );

		it( `with previous state,
	 multiple items,
	 and optimisticQueryUpdate with order_by: name,
	 and optimisticUrlParameters options`, () => {
			const item: Item = {
				id: 9,
				name: 'Mootools',
				status: 'draft',
			};

			const query = {
				name: 'Mootools',
				status: 'draft',
				parent_id: 500,
			};

			const options = {
				optimisticQueryUpdate: { parent_id: 500, order_by: 'name' },
				optimisticUrlParameters: [ 500 ],
			};

			const getItemsQueryId_200 = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				{
					parent_id: 200,
					rocking_by: 'name',
				}
			);

			const getItemsQueryId_300 = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				{
					parent_id: 300,
					rocking_by: 'name',
				}
			);

			const getItemsQueryId = getRequestIdentifier(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const getItemsCountQueryId_200 = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				{
					parent_id: 200,
					rocking_by: 'name',
				}
			);

			const getItemsCountQueryId_300 = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				{
					parent_id: 300,
					order_by: 'name',
				}
			);

			const getItemsCountQueryId = getTotalCountResourceName(
				CRUD_ACTIONS.GET_ITEMS,
				options.optimisticQueryUpdate
			);

			const initialState: ResourceState = {
				items: {
					[ getItemsQueryId_200 ]: {
						data: [ '500/1', '500/2' ],
					},
					[ getItemsQueryId_300 ]: {
						data: [ '300/1', '300/2' ],
					},
					[ getItemsQueryId ]: {
						data: [ '500/1', '500/2', '500/3' ],
					},
				},
				itemsCount: {
					[ getItemsCountQueryId_200 ]: 2,
					[ getItemsCountQueryId_300 ]: 2,
					[ getItemsCountQueryId ]: 3,
				},
				errors: {},
				data: {
					'200/1': { id: 1, name: 'apple', status: 'draft' },
					'200/2': { id: 2, name: 'pine', status: 'publish' },
					'300/1': { id: 1, name: 'cat', status: 'draft' },
					'300/2': { id: 2, name: 'dog', status: 'draft' },
					'500/1': { id: 1, name: 'jQuery', status: 'draft' },
					'500/2': { id: 2, name: 'AlphaPro', status: 'draft' },
					'500/3': { id: 3, name: 'Vue', status: 'draft' },
				},
				requesting: {},
			};

			const state = reducer( initialState, {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: item.id,
				item,
				query,
				options,
			} );

			expect( state.data ).toEqual( {
				'200/1': { id: 1, name: 'apple', status: 'draft' },
				'200/2': { id: 2, name: 'pine', status: 'publish' },
				'300/1': { id: 1, name: 'cat', status: 'draft' },
				'300/2': { id: 2, name: 'dog', status: 'draft' },
				'500/1': { id: 1, name: 'jQuery', status: 'draft' },
				'500/2': { id: 2, name: 'AlphaPro', status: 'draft' },
				'500/3': { id: 3, name: 'Vue', status: 'draft' },
				'500/9': { id: 9, name: 'Mootools', status: 'draft' }, // New item
			} );

			const resourceName = getRequestIdentifier(
				CRUD_ACTIONS.CREATE_ITEM,
				'500/9',
				query
			);

			expect( state.requesting[ resourceName ] ).toEqual( false );

			expect( state.items[ getItemsQueryId ] ).toBeDefined();
			expect( state.items[ getItemsQueryId ].data ).toEqual( [
				'500/2',
				'500/1',
				'500/9',
				'500/3',
			] );

			expect( state.itemsCount[ getItemsCountQueryId ] ).toEqual( 4 );
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
