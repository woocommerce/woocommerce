/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { createDispatchActions } from '../actions';
import TYPES from '../action-types';
import { cleanQuery, getRestPath, getUrlParameters } from '../utils';
import type { Item } from '../types';

const selectors = createDispatchActions( {
	resourceName: 'Product',
	namespace: '/products',
} );

describe( 'crud actions', () => {
	it( 'should return methods for the default actions', () => {
		expect( Object.keys( selectors ).length ).toEqual( 3 );
		expect( selectors ).toHaveProperty( 'createProduct' );
		expect( selectors ).toHaveProperty( 'deleteProduct' );
		expect( selectors ).toHaveProperty( 'updateProduct' );
	} );

	describe( 'should create a new item', () => {
		const namespace = 'test-namespace';
		const resourceName = 'MyThing';
		const { createMyThing } = createDispatchActions( {
			namespace,
			resourceName,
		} );

		it( 'with regular query params', async () => {
			const query = {
				name: 'Zuri',
				kind: 'dog',
				age: 3,
			};

			const expectedItem: Item = {
				id: 1,
				name: 'Zuri',
				kind: 'dog',
				age: 3,
			};

			const dispatch = jest.fn();

			// Call the createMyThing action
			const generator = createMyThing( query );

			// Step through the generator request action
			const { value: requestAction } = generator.next();

			expect( requestAction ).toEqual( {
				type: TYPES.CREATE_ITEM_REQUEST,
				query,
			} );

			// Step through the generator fetch call
			const urlParameters = getUrlParameters( namespace, query );
			const path = getRestPath(
				namespace,
				cleanQuery( query, namespace ),
				urlParameters
			);

			const { value: apiFetchCall } = generator.next();

			expect( apiFetchCall ).toEqual(
				apiFetch( {
					path: expect.stringContaining( path ),
					method: 'POST',
				} )
			);

			// Continue the generator with the apiFetch response
			const { value: successAction } = generator.next( expectedItem );

			expect( successAction ).toEqual( {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: 1,
				item: expectedItem,
				query,
			} );

			// Dispatch the actions
			dispatch( requestAction );

			// Check the actions dispatched to the store
			expect( dispatch ).toHaveBeenCalledWith( {
				type: TYPES.CREATE_ITEM_REQUEST,
				query,
			} );

			dispatch( successAction );
			expect( dispatch ).toHaveBeenCalledWith( {
				type: TYPES.CREATE_ITEM_SUCCESS,
				key: 1,
				item: expectedItem,
				query,
			} );
		} );

		it( 'with optimistc propagation', async () => {
			const query = {
				name: 'Zuri',
				kind: 'dog',
				age: 3,
			};

			// Call the createMyThing action
			const generator = createMyThing( query, {
				optimisticPropagation: true,
			} );

			// Step through the generator request action
			const { value: requestAction } = generator.next();

			expect( requestAction.type ).toEqual( TYPES.CREATE_ITEM_REQUEST );
			expect( requestAction.query ).toEqual( {
				name: 'Zuri',
				kind: 'dog',
				age: 3,
			} );

			expect( requestAction.options.optimisticPropagation ).toEqual(
				true
			);

			// Test the options.tmpId has a temporary id shape, starting with 'temp-'
			expect( requestAction.options.tempId ).toMatch( /^temp-/ );
		} );
	} );
} );
