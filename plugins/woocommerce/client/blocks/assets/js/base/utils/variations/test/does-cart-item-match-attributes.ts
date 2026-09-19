/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type {
	OptimisticCartItem,
	SelectedAttributes,
	WooCommerceStore,
} from '@woocommerce/stores/woocommerce';

let mockWooState: WooCommerceStore[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( () => ( { state: mockWooState } ) ),
	} ),
	{ virtual: true }
);

const mockProduct = ( overrides: Partial< ProductResponseItem > = {} ) =>
	( {
		id: 1,
		type: 'simple',
		attributes: [],
		...overrides,
	} ) as ProductResponseItem;

const buildCartItem = (
	variation: Array< { attribute: string; value: string } >
) => ( { id: 501, variation } ) as unknown as OptimisticCartItem;

describe( 'doesCartItemMatchAttributes', () => {
	let doesCartItemMatchAttributes: typeof import('../does-cart-item-match-attributes').doesCartItemMatchAttributes;

	beforeEach( () => {
		mockWooState = {
			products: {},
			productVariations: {},
		} as WooCommerceStore[ 'state' ];

		jest.isolateModules( () => {
			( {
				doesCartItemMatchAttributes,
			} = require( '../does-cart-item-match-attributes' ) );
		} );
	} );

	it( 'returns false when the cart item has no variation array', () => {
		expect(
			doesCartItemMatchAttributes(
				{ id: 501 } as OptimisticCartItem,
				[] as SelectedAttributes[]
			)
		).toBe( false );
	} );

	it( 'returns false when the selected attributes are not an array', () => {
		expect(
			doesCartItemMatchAttributes(
				buildCartItem( [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
				] ),
				undefined as unknown as SelectedAttributes[]
			)
		).toBe( false );
	} );

	it( 'returns false when the attribute counts differ', () => {
		mockWooState.productVariations[ 501 ] = mockProduct( {
			id: 501,
			parent: 100,
		} );
		mockWooState.products[ 100 ] = mockProduct( {
			id: 100,
			attributes: [
				{
					id: 1,
					name: 'attribute_pa_color',
					taxonomy: 'pa_color',
					has_variations: true,
					terms: [ { id: 1, name: 'Blue', slug: 'blue' } ],
				},
			],
		} );

		const result = doesCartItemMatchAttributes(
			buildCartItem( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			] ),
			[]
		);

		expect( result ).toBe( false );
	} );

	it( 'matches by resolving the cart item value label to its term slug', () => {
		mockWooState.productVariations[ 501 ] = mockProduct( {
			id: 501,
			parent: 100,
		} );
		mockWooState.products[ 100 ] = mockProduct( {
			id: 100,
			attributes: [
				{
					id: 1,
					name: 'attribute_pa_color',
					taxonomy: 'pa_color',
					has_variations: true,
					terms: [ { id: 1, name: 'Blue', slug: 'blue' } ],
				},
			],
		} );

		const result = doesCartItemMatchAttributes(
			buildCartItem( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			] ),
			[ { attribute: 'Color', value: 'blue' } ]
		);

		expect( result ).toBe( true );
	} );

	it( 'falls back to the raw value label when no matching term is found', () => {
		mockWooState.productVariations[ 501 ] = mockProduct( {
			id: 501,
			parent: 100,
		} );
		mockWooState.products[ 100 ] = mockProduct( {
			id: 100,
			attributes: [],
		} );

		const result = doesCartItemMatchAttributes(
			buildCartItem( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			] ),
			[ { attribute: 'Color', value: 'blue' } ]
		);

		expect( result ).toBe( true );
	} );

	it( 'returns false when the selected attribute value does not match', () => {
		mockWooState.productVariations[ 501 ] = mockProduct( {
			id: 501,
			parent: 100,
		} );
		mockWooState.products[ 100 ] = mockProduct( {
			id: 100,
			attributes: [
				{
					id: 1,
					name: 'attribute_pa_color',
					taxonomy: 'pa_color',
					has_variations: true,
					terms: [ { id: 1, name: 'Blue', slug: 'blue' } ],
				},
			],
		} );

		const result = doesCartItemMatchAttributes(
			buildCartItem( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			] ),
			[ { attribute: 'Color', value: 'red' } ]
		);

		expect( result ).toBe( false );
	} );

	it( 'matches every selected attribute for a multi-attribute variation', () => {
		mockWooState.productVariations[ 501 ] = mockProduct( {
			id: 501,
			parent: 100,
		} );
		mockWooState.products[ 100 ] = mockProduct( {
			id: 100,
			attributes: [
				{
					id: 1,
					name: 'attribute_pa_color',
					taxonomy: 'pa_color',
					has_variations: true,
					terms: [ { id: 1, name: 'Blue', slug: 'blue' } ],
				},
				{
					id: 2,
					name: 'attribute_pa_size',
					taxonomy: 'pa_size',
					has_variations: true,
					terms: [ { id: 2, name: 'Medium', slug: 'medium' } ],
				},
			],
		} );

		const result = doesCartItemMatchAttributes(
			buildCartItem( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			] ),
			[
				{ attribute: 'Color', value: 'blue' },
				{ attribute: 'Size', value: 'medium' },
			]
		);

		expect( result ).toBe( true );
	} );
} );
