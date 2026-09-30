/**
 * External dependencies
 */
import type { OptimisticCartItem } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import { getInCartQuantity } from '../utils';

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( () => ( {
			state: {
				productVariations: {},
				products: {},
			},
		} ) ),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );

/**
 * An optimistic cart item with optional response-only fields used by these tests.
 */
type TestCartItem = OptimisticCartItem & {
	/** Cart key of the parent line, when this line is a declared child. */
	parent_item_key?: string | null;
	/** Custom item data attached to the line. */
	item_data?: unknown[];
};

/**
 * Create an optimistic cart line for quantity-counting tests.
 *
 * @param id        Product or variation ID.
 * @param quantity  Quantity on the cart line.
 * @param overrides Additional cart-line properties.
 * @return The cart line with simple-product defaults.
 */
const makeCartItem = (
	id: number,
	quantity: number,
	overrides: Partial< TestCartItem > = {}
): TestCartItem => ( {
	id,
	quantity,
	type: 'simple',
	...overrides,
} );

describe( 'getInCartQuantity', () => {
	it( 'sums lines for the product and skips declared children', () => {
		const items = [
			makeCartItem( 10, 1 ),
			makeCartItem( 10, 2, { parent_item_key: null } ),
			makeCartItem( 10, 3, { parent_item_key: '' } ),
			makeCartItem( 10, 4, { item_data: [ { key: 'engraving' } ] } ),
			makeCartItem( 10, 5, { parent_item_key: 'parent-a' } ),
			makeCartItem( 11, 6 ),
		];

		expect( getInCartQuantity( items, { id: 10 } ) ).toBe( 10 );
	} );

	it( 'matches simple and variation lines by ID when no selection is provided', () => {
		const items = [
			makeCartItem( 10, 3 ),
			makeCartItem( 21, 2, { type: 'variation' } ),
		];

		expect( getInCartQuantity( items, { id: 10 } ) ).toBe( 3 );
		expect( getInCartQuantity( items, { id: 21 } ) ).toBe( 2 );
		expect( getInCartQuantity( items, { id: 20 } ) ).toBe( 0 );
		expect( getInCartQuantity( items, { id: 99 } ) ).toBe( 0 );
	} );

	it( 'filters variation lines by selected attributes and excludes declared children', () => {
		const items = [
			makeCartItem( 21, 2, {
				type: 'variation',
				variation: [
					{
						attribute: 'Color',
						value: 'Blue',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} ),
			makeCartItem( 21, 7, {
				type: 'variation',
				variation: [
					{
						attribute: 'Color',
						value: 'Green',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} ),
			makeCartItem( 21, 100, {
				type: 'variation',
				parent_item_key: 'parent-a',
				variation: [
					{
						attribute: 'Color',
						value: 'Blue',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} ),
			makeCartItem( 22, 5, {
				type: 'variation',
				variation: [
					{
						attribute: 'Color',
						value: 'Red',
						raw_attribute: 'attribute_pa_color',
					},
				],
			} ),
		];

		expect(
			getInCartQuantity( items, {
				id: 21,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} )
		).toBe( 2 );
		expect(
			getInCartQuantity( items, {
				id: 21,
				selectedAttributes: [ { attribute: 'Color', value: 'green' } ],
			} )
		).toBe( 7 );
		expect(
			getInCartQuantity( items, {
				id: 22,
				selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
			} )
		).toBe( 5 );
		expect(
			getInCartQuantity( items, { id: 21, selectedAttributes: [] } )
		).toBe( 0 );
		expect(
			getInCartQuantity( items, {
				id: 20,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} )
		).toBe( 0 );
	} );

	it( 'preserves fractional quantities', () => {
		const items = [ makeCartItem( 10, 1.5 ), makeCartItem( 10, 2 ) ];

		expect( getInCartQuantity( items, { id: 10 } ) ).toBe( 3.5 );
	} );
} );
