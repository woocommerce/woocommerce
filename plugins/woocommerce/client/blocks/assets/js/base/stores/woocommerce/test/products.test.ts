/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ProductsStore } from '../products';

let mockRegisteredStore: {
	state: ProductsStore[ 'state' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				const stateBase = {
					products: {} as Record< number, ProductResponseItem >,
					productVariations: {} as Record<
						number,
						ProductResponseItem
					>,
				};
				Object.assign( stateBase, definition.state );
				mockRegisteredStore = {
					state: stateBase as ProductsStore[ 'state' ],
				};
				return mockRegisteredStore;
			}
			return {};
		} ),
	} ),
	{ virtual: true }
);

describe( 'getProduct', () => {
	beforeEach( () => {
		mockRegisteredStore = null;
		jest.isolateModules( () => require( '../products' ) );
	} );

	it( 'returns null when product is not in store', () => {
		const result = mockRegisteredStore!.state.getProduct( { id: 999 } );
		expect( result ).toBeNull();
	} );

	it( 'returns the product for a simple product', () => {
		const product = {
			id: 1,
			type: 'simple',
		} as ProductResponseItem;
		mockRegisteredStore!.state.products[ 1 ] = product;

		const result = mockRegisteredStore!.state.getProduct( { id: 1 } );
		expect( result ).toBe( product );
	} );

	it( 'returns the product when no selectedAttributes are provided', () => {
		const product = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as ProductResponseItem;
		mockRegisteredStore!.state.products[ 1 ] = product;

		const result = mockRegisteredStore!.state.getProduct( { id: 1 } );
		expect( result ).toBe( product );
	} );

	it( 'returns the product when selectedAttributes is empty', () => {
		const product = {
			id: 1,
			type: 'variable',
			variations: [
				{
					id: 10,
					attributes: [ { name: 'Color', value: 'red' } ],
				},
			],
		} as ProductResponseItem;
		mockRegisteredStore!.state.products[ 1 ] = product;

		const result = mockRegisteredStore!.state.getProduct( {
			id: 1,
			selectedAttributes: [],
		} );
		expect( result ).toBe( product );
	} );

	describe( 'variable products', () => {
		it( 'returns the variation when attributes match', () => {
			const product = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as ProductResponseItem;
			const variation = {
				id: 10,
				type: 'variation',
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;
			mockRegisteredStore!.state.productVariations[ 10 ] = variation;

			const result = mockRegisteredStore!.state.getProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );
			expect( result ).toBe( variation );
		} );

		it( 'returns null when variation is matched but not in productVariations', () => {
			const product = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;

			const result = mockRegisteredStore!.state.getProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} );
			expect( result ).toBeNull();
		} );

		it( 'returns null when no variation matches the attributes', () => {
			const product = {
				id: 1,
				type: 'variable',
				variations: [
					{
						id: 10,
						attributes: [ { name: 'Color', value: 'red' } ],
					},
				],
			} as ProductResponseItem;
			mockRegisteredStore!.state.products[ 1 ] = product;

			const result = mockRegisteredStore!.state.getProduct( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );
			expect( result ).toBeNull();
		} );
	} );
} );
