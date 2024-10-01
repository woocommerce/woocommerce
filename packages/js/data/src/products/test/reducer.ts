/**
 * Internal dependencies
 */
import reducer, { ProductState } from '../reducer';
import TYPES from '../action-types';
import {
	getProductResourceName,
	getTotalProductCountResourceName,
} from '../utils';
import { Actions } from '../actions';
import { PartialProduct, ProductQuery, Product } from '../types';

const defaultState: ProductState = {
	products: {},
	productsCount: {},
	errors: {},
	data: {},
	pending: {},
	suggestedProducts: {},
};

describe( 'products reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} as Actions );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle GET_PRODUCT_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ProductState = {
			products: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			productsCount: {
				'total-guyisms:{}': 2,
			},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			pending: {},
			suggestedProducts: {},
		};
		const update: PartialProduct = {
			id: 2,
			status: 'draft',
		};

		const state = reducer( initialState, {
			type: TYPES.GET_PRODUCT_SUCCESS,
			id: update.id,
			product: update,
		} );

		expect( state.products ).toEqual( initialState.products );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].status ).toEqual( update.status );
	} );

	it( 'should handle GET_PRODUCTS_SUCCESS', () => {
		const products: PartialProduct[] = [
			{ id: 1, name: 'Yum!' },
			{ id: 2, name: 'Dynamite!' },
		];
		const totalCount = 45;
		const query: Partial< ProductQuery > = { status: 'draft' };
		const state = reducer( defaultState, {
			type: TYPES.GET_PRODUCTS_SUCCESS,
			products,
			query,
			totalCount,
		} );

		const resourceName = getProductResourceName( query );

		expect( state.products[ resourceName ].data ).toHaveLength( 2 );
		expect(
			state.products[ resourceName ].data.includes( 1 )
		).toBeTruthy();
		expect(
			state.products[ resourceName ].data.includes( 2 )
		).toBeTruthy();

		expect( state.data[ 1 ] ).toEqual( products[ 0 ] );
		expect( state.data[ 2 ] ).toEqual( products[ 1 ] );
	} );

	it( 'GET_PRODUCTS_SUCCESS should not remove previously added fields, only update new ones', () => {
		const initialState: ProductState = {
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

		const products: PartialProduct[] = [
			{ id: 1, name: 'Yum! Updated' },
			{ id: 2, name: 'Dynamite!' },
		];
		const totalCount = 45;
		const query: Partial< ProductQuery > = { status: 'draft' };
		const state = reducer( initialState, {
			type: TYPES.GET_PRODUCTS_SUCCESS,
			products,
			query,
			totalCount,
		} );

		const resourceName = getProductResourceName( query );

		expect( state.products[ resourceName ].data ).toHaveLength( 2 );
		expect(
			state.products[ resourceName ].data.includes( 1 )
		).toBeTruthy();
		expect(
			state.products[ resourceName ].data.includes( 2 )
		).toBeTruthy();

		expect( state.data[ 1 ].name ).toEqual( products[ 0 ].name );
		expect( state.data[ 1 ].price ).toEqual( initialState.data[ 1 ].price );
		expect( state.data[ 1 ].description ).toEqual(
			initialState.data[ 1 ].description
		);
		expect( state.data[ 2 ] ).toEqual( initialState.data[ 2 ] );
	} );

	it( 'should handle GET_PRODUCTS_TOTAL_COUNT_SUCCESS', () => {
		const initialQuery: Partial< ProductQuery > = {
			status: 'publish',
			page: 1,
			per_page: 1,
			_fields: [ 'id' ],
		};
		const resourceName = getTotalProductCountResourceName( initialQuery );
		const initialState: ProductState = {
			...defaultState,
			productsCount: {
				[ resourceName ]: 1,
			},
		};

		// Additional coverage for getTotalCountResourceName().
		const similarQueryForTotals: Partial< ProductQuery > = {
			status: 'publish',
			page: 2,
			per_page: 10,
			_fields: [ 'id', 'title', 'status' ],
		};

		const state = reducer( initialState, {
			type: TYPES.GET_PRODUCTS_TOTAL_COUNT_SUCCESS,
			query: similarQueryForTotals,
			totalCount: 2,
		} );

		expect( state.productsCount ).toEqual( {
			[ resourceName ]: 2,
		} );
	} );

	it( 'should handle GET_PRODUCTS_ERROR', () => {
		const query: Partial< ProductQuery > = { status: 'draft' };
		const resourceName = getProductResourceName( query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_PRODUCTS_ERROR,
			query,
			error,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle CREATE_PRODUCT_SUCCESS', () => {
		const update: PartialProduct = {
			id: 2,
			name: 'Off the hook!',
			status: 'draft',
		};

		const state = reducer(
			{
				...defaultState,
				pending: {
					createProduct: true,
				},
			},
			{
				type: TYPES.CREATE_PRODUCT_SUCCESS,
				id: update.id,
				product: update,
			}
		);

		expect( state.data[ 2 ].name ).toEqual( update.name );
		expect( state.data[ 2 ].status ).toEqual( update.status );
		expect( state.pending.createProduct ).toEqual( false );
	} );

	it( 'should handle CREATE_PRODUCT_ERROR', () => {
		const query: Partial< Product > = { name: 'Donkey Sauce' };
		const resourceName = getProductResourceName( query );
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.GET_PRODUCTS_ERROR,
			query,
			error,
		} );

		expect( state.errors[ resourceName ] ).toBe( error );
	} );

	it( 'should handle CREATE_PRODUCT_START', () => {
		const state = reducer( defaultState, {
			type: TYPES.CREATE_PRODUCT_START,
		} );

		expect( state.pending.createProduct ).toEqual( true );
	} );

	it( 'should handle UPDATE_PRODUCT_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ProductState = {
			products: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			productsCount: {
				'total-guyisms:{}': 2,
			},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			pending: {
				updateProduct: {
					1: false,
					2: true,
				},
			},
			suggestedProducts: {},
		};
		const product: PartialProduct = {
			id: 2,
			name: 'Holy smokes!',
			status: 'draft',
		};

		const state = reducer( initialState, {
			type: TYPES.UPDATE_PRODUCT_SUCCESS,
			id: product.id,
			product,
		} );

		expect( state.products ).toEqual( initialState.products );
		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ] ).toEqual( initialState.data[ 1 ] );
		expect( state.data[ 2 ].id ).toEqual( initialState.data[ 2 ].id );
		expect( state.data[ 2 ].title ).toEqual( initialState.data[ 2 ].title );
		expect( state.data[ 2 ].name ).toEqual( product.name );
		expect( ( state.pending.updateProduct || {} )[ product.id ] ).toEqual(
			false
		);
		expect( ( state.pending.updateProduct || {} )[ 1 ] ).toEqual( false );
	} );

	it( 'should handle UPDATE_PRODUCT_ERROR', () => {
		const id = 1;
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_PRODUCT_ERROR,
			id,
			error,
		} );

		expect( state.errors[ `update/${ id }` ] ).toBe( error );
	} );

	it( 'should handle UPDATE_PRODUCT_START', () => {
		const id = 1;
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_PRODUCT_START,
			id,
		} );

		expect( ( state.pending.updateProduct || {} )[ id ] ).toEqual( true );
	} );

	it( 'should handle DELETE_PRODUCT_SUCCESS', () => {
		const itemType = 'guyisms';
		const initialState: ProductState = {
			products: {
				[ itemType ]: {
					data: [ 1, 2 ],
				},
			},
			productsCount: {
				'total-guyisms:{}': 2,
			},
			errors: {},
			data: {
				1: { id: 1, name: 'Donkey', status: 'draft' },
				2: { id: 2, name: 'Sauce', status: 'publish' },
			},
			pending: {
				deleteProduct: {
					1: true,
					2: true,
				},
			},
			suggestedProducts: {},
		};
		const product: PartialProduct = {
			id: 1,
			status: 'pending',
		};
		const anotherProduct: PartialProduct = {
			id: 2,
			status: 'draft',
		};

		let state = reducer( initialState, {
			type: TYPES.DELETE_PRODUCT_SUCCESS,
			id: product.id,
			product,
			force: false,
		} );
		state = reducer( state, {
			type: TYPES.DELETE_PRODUCT_SUCCESS,
			id: anotherProduct.id,
			product: anotherProduct,
			force: true,
		} );

		expect( state.errors ).toEqual( initialState.errors );

		expect( state.data[ 1 ].status ).toEqual( 'trash' );
		expect( state.data[ 2 ].status ).toEqual( 'deleted' );
		expect( ( state.pending.deleteProduct || {} )[ product.id ] ).toEqual(
			false
		);
		expect(
			( state.pending.deleteProduct || {} )[ anotherProduct.id ]
		).toEqual( false );
	} );

	it( 'should handle DELETE_PRODUCT_ERROR', () => {
		const id = 1;
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.DELETE_PRODUCT_ERROR,
			id,
			error,
		} );

		expect( state.errors[ `delete/${ id }` ] ).toBe( error );
	} );

	it( 'should handle DELETE_PRODUCT_START', () => {
		const id = 1;
		const state = reducer( defaultState, {
			type: TYPES.DELETE_PRODUCT_START,
			id,
		} );

		expect( ( state.pending.deleteProduct || {} )[ id ] ).toEqual( true );
	} );
} );
