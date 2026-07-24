/**
 * External dependencies
 */
import type { CartItem, ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { DraftItem, OptimisticCartItem } from '../cart';

/**
 * The shared `woocommerce` namespace's nested product/variation maps, held
 * stable across the repeated `store()` calls this module makes (once at
 * `cart-pairing.ts`'s own module scope). Tests mutate `products.items`/
 * `products.variations` directly, in `beforeEach`, rather than reassigning
 * this container — `cart-pairing.ts` re-resolves `state` fresh on every
 * call, so only a mutation of the already-captured object is ever visible
 * to it.
 */
const mockState: {
	products: {
		items: Record< number, ProductResponseItem >;
		variations: Record< number, ProductResponseItem >;
	};
} = {
	products: { items: {}, variations: {} },
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( () => ( { state: mockState } ) ),
	} ),
	{ virtual: true }
);

// eslint-disable-next-line import/order
import {
	draftExtensionsMatchLine,
	findCartLine,
	lineMatchesProduct,
	matchesSelectedAttributes,
	resolveBaseProduct,
} from '../cart-pairing';

beforeEach( () => {
	mockState.products = { items: {}, variations: {} };
} );

/**
 * Builds a minimal server-confirmed cart line, optionally carrying
 * namespaced extension data under `extensions[namespace]`.
 *
 * @param overrides Partial cart-line fields to override the defaults.
 * @return A cart line suitable for use as a pairing-ladder candidate.
 */
function makeLine( overrides: Partial< CartItem > = {} ): CartItem {
	return {
		key: 'server-key-abc',
		id: 42,
		type: 'simple',
		quantity: 3,
		name: 'Test Product',
		sold_individually: false,
		variation: [],
		item_data: [],
		extensions: {},
		...overrides,
	} as CartItem;
}

describe( 'resolveBaseProduct', () => {
	it( 'returns null when the id names neither a product nor a variation', () => {
		expect( resolveBaseProduct( 999 ) ).toBeNull();
	} );

	it( 'returns the product itself when id names a top-level product', () => {
		const product = { id: 10, type: 'simple' } as ProductResponseItem;
		mockState.products.items = { 10: product };

		expect( resolveBaseProduct( 10 ) ).toBe( product );
	} );

	it( 'returns the parent product when id names one of its variations', () => {
		const baseProduct = {
			id: 10,
			type: 'variable',
		} as ProductResponseItem;
		mockState.products.items = { 10: baseProduct };
		mockState.products.variations = {
			20: { id: 20, parent: 10 } as ProductResponseItem,
		};

		expect( resolveBaseProduct( 20 ) ).toBe( baseProduct );
	} );

	it( 'returns null when the variation is known but its parent is not', () => {
		mockState.products.variations = {
			20: { id: 20, parent: 999 } as ProductResponseItem,
		};

		expect( resolveBaseProduct( 20 ) ).toBeNull();
	} );
} );

describe( 'matchesSelectedAttributes', () => {
	it( 'returns false when the cart line carries no variation array', () => {
		const cartItem = {
			id: 20,
			variation: undefined,
		} as unknown as OptimisticCartItem;

		expect(
			matchesSelectedAttributes( cartItem, [
				{ attribute: 'Color', value: 'blue' },
			] )
		).toBe( false );
	} );

	it( 'returns false when the selected-attributes count differs from the recorded count', () => {
		const cartItem = {
			id: 20,
			variation: [ { attribute: 'Color', value: 'blue' } ],
		} as unknown as OptimisticCartItem;

		expect( matchesSelectedAttributes( cartItem, [] ) ).toBe( false );
	} );

	it( 'matches directly when the recorded value equals the selected value, case-insensitively', () => {
		const cartItem = {
			id: 20,
			variation: [
				{
					attribute: 'Color',
					value: 'green',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} as unknown as OptimisticCartItem;

		expect(
			matchesSelectedAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'GREEN' },
			] )
		).toBe( true );
	} );

	it( 'returns false when the recorded value does not equal the selected value', () => {
		const cartItem = {
			id: 20,
			variation: [
				{
					attribute: 'Color',
					value: 'green',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} as unknown as OptimisticCartItem;

		expect(
			matchesSelectedAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			] )
		).toBe( false );
	} );

	it( 'resolves the recorded term name to its slug via the parent product before comparing', () => {
		mockState.products.items = {
			10: {
				id: 10,
				type: 'variable',
				attributes: [
					{
						id: 1,
						name: 'Color',
						taxonomy: 'pa_color',
						has_variations: true,
						terms: [ { id: 1, name: 'Blue', slug: 'blue' } ],
					},
				],
			} as unknown as ProductResponseItem,
		};
		mockState.products.variations = {
			20: { id: 20, parent: 10 } as ProductResponseItem,
		};
		// The recorded value is the term's display name ("Blue"); the
		// selected value is its slug ("blue") — the label/slug mismatch
		// this resolution reconciles.
		const cartItem = {
			id: 20,
			variation: [ { attribute: 'Color', value: 'Blue' } ],
		} as unknown as OptimisticCartItem;

		expect(
			matchesSelectedAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			] )
		).toBe( true );
	} );
} );

describe( 'lineMatchesProduct', () => {
	it( 'matches a simple item by id equality', () => {
		const item = makeLine( { id: 42, type: 'simple' } );

		expect( lineMatchesProduct( item, 42 ) ).toBe( true );
		expect( lineMatchesProduct( item, 99 ) ).toBe( false );
	} );

	it( 'returns false for a variation item when the id differs', () => {
		const item = makeLine( {
			id: 20,
			type: 'variation',
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			lineMatchesProduct( item, 21, [
				{ attribute: 'Color', value: 'blue' },
			] )
		).toBe( false );
	} );

	it( 'returns false for a variation item when no variation argument is given', () => {
		const item = makeLine( {
			id: 20,
			type: 'variation',
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect( lineMatchesProduct( item, 20 ) ).toBe( false );
	} );

	it( 'returns false for a variation item when variation lengths differ', () => {
		const item = makeLine( {
			id: 20,
			type: 'variation',
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			lineMatchesProduct( item, 20, [
				{ attribute: 'Color', value: 'blue' },
				{ attribute: 'Size', value: 'Large' },
			] )
		).toBe( false );
	} );

	it( 'matches a variation item via matchesSelectedAttributes when id and attributes agree', () => {
		const item = makeLine( {
			id: 20,
			type: 'variation',
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			lineMatchesProduct( item, 20, [
				{ attribute: 'Color', value: 'blue' },
			] )
		).toBe( true );
	} );
} );

describe( 'findCartLine', () => {
	it( 'returns undefined without throwing when given an empty items array', () => {
		expect( findCartLine( [], { id: 42 } ) ).toBeUndefined();
		expect( findCartLine( [], { key: 'any-key' } ) ).toBeUndefined();
	} );

	it( 'pairs by key exactly, regardless of id/variation', () => {
		const line = makeLine( { id: 42, key: 'the-key' } );
		const other = makeLine( { id: 99, key: 'other-key' } );

		expect(
			findCartLine( [ other, line ], { id: 1, key: 'the-key' } )
		).toBe( line );
	} );

	it( 'returns undefined when key is given but no line carries it', () => {
		const line = makeLine( { id: 42, key: 'the-key' } );

		expect(
			findCartLine( [ line ], { key: 'missing-key' } )
		).toBeUndefined();
	} );

	it( 'falls back to identity matching when no key is given', () => {
		const line = makeLine( { id: 42, key: 'the-key' } );
		const other = makeLine( { id: 99, key: 'other-key' } );

		expect( findCartLine( [ other, line ], { id: 42 } ) ).toBe( line );
	} );

	it( 'returns undefined when no line matches by identity', () => {
		const line = makeLine( { id: 42, key: 'the-key' } );

		expect( findCartLine( [ line ], { id: 99 } ) ).toBeUndefined();
	} );
} );

describe( 'draftExtensionsMatchLine', () => {
	it( 'matches when the draft carries no extension props, regardless of the line', () => {
		const item = makeLine( { extensions: {} } );

		expect( draftExtensionsMatchLine( undefined, item ) ).toBe( true );
		expect(
			draftExtensionsMatchLine( { id: 42, quantity: 1 }, item )
		).toBe( true );
	} );

	it( 'matches when every namespaced extension prop deep-equals the line data', () => {
		const item = makeLine( {
			extensions: { 'my-plugin': { giftNote: { text: 'Hi' } } },
		} );
		const draft = {
			id: 42,
			quantity: 1,
			'my-plugin/giftNote': { text: 'Hi' },
		} as DraftItem;

		expect( draftExtensionsMatchLine( draft, item ) ).toBe( true );
	} );

	it( 'does not match when an extension prop value differs', () => {
		const item = makeLine( {
			extensions: { 'my-plugin': { giftNote: 'A' } },
		} );
		const draft = {
			id: 42,
			quantity: 1,
			'my-plugin/giftNote': 'B',
		} as DraftItem;

		expect( draftExtensionsMatchLine( draft, item ) ).toBe( false );
	} );

	it( 'does not match when the line carries no data under the extension namespace at all', () => {
		const item = makeLine( { extensions: {} } );
		const draft = {
			id: 42,
			quantity: 1,
			'my-plugin/giftNote': 'A',
		} as DraftItem;

		expect( draftExtensionsMatchLine( draft, item ) ).toBe( false );
	} );
} );
