/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	OptimisticCartItem,
	SelectedAttributes,
} from '@woocommerce/stores/woocommerce/cart';

type Matcher = (
	cartItem: OptimisticCartItem,
	selectedAttributes: SelectedAttributes[]
) => boolean;

// The unified `woocommerce` namespace's nested product/variation maps, as
// consulted by `does-cart-item-match-attributes.ts`'s own module-scope
// `store()` call. Declared with `let` (no initializer) and populated fresh
// in `beforeEach`; the module under test is then loaded via
// `jest.isolateModules` (see `loadMatcher()`) rather than a static top-level
// `import`, so its eager `store()` call always resolves against that test's
// own fixture instead of whatever existed at file-evaluation time.
let mockState: {
	products: {
		items: Record< number, ProductResponseItem >;
		variations: Record< number, ProductResponseItem >;
	};
};

// Every namespace `store()` is called with, recorded to prove the module
// opens the unified `woocommerce` namespace rather than the retired
// `woocommerce/products` one.
let mockCapturedStoreNames: string[];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( name: string ) => {
			mockCapturedStoreNames.push( name );
			return { state: mockState };
		} ),
	} ),
	{ virtual: true }
);

// Side-effect-only import `does-cart-item-match-attributes.ts` makes for
// module ordering; the mocked `store()` above handles the registration
// directly. The real root module must never load here — it would otherwise
// make its own eager `store()` call, independent of this file's fixtures.
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
	virtual: true,
} );

/**
 * Loads a fresh copy of `does-cart-item-match-attributes.ts` so its
 * module-scope `store()` call resolves against the current fixture.
 *
 * @return The module's exported matcher function.
 */
function loadMatcher(): Matcher {
	let matcher: Matcher | undefined;
	jest.isolateModules( () => {
		( {
			doesCartItemMatchAttributes: matcher,
		} = require( '../does-cart-item-match-attributes' ) );
	} );
	if ( ! matcher ) {
		throw new Error( 'doesCartItemMatchAttributes was not exported.' );
	}
	return matcher;
}

/**
 * Builds a minimal variation cart line carrying a `variation` array, for
 * matching against selected attributes.
 *
 * @param overrides Partial cart-line fields to override the defaults.
 * @return A cart line suitable for `doesCartItemMatchAttributes`.
 */
function makeCartItem(
	overrides: Partial< OptimisticCartItem > = {}
): OptimisticCartItem {
	return {
		id: 20,
		type: 'variation',
		quantity: 1,
		variation: [],
		...overrides,
	} as OptimisticCartItem;
}

beforeEach( () => {
	mockState = { products: { items: {}, variations: {} } };
	mockCapturedStoreNames = [];
} );

afterEach( () => {
	jest.clearAllMocks();
} );

describe( 'module registration', () => {
	it( 'opens the unified woocommerce store, never the retired woocommerce/products namespace', () => {
		loadMatcher();

		expect( mockCapturedStoreNames ).toContain( 'woocommerce' );
		expect( mockCapturedStoreNames ).not.toContain(
			'woocommerce/products'
		);
	} );
} );

describe( 'doesCartItemMatchAttributes', () => {
	it( 'returns false when the cart line carries no variation array', () => {
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = {
			id: 20,
			type: 'variation',
			quantity: 1,
			variation: undefined,
		} as unknown as OptimisticCartItem;

		expect(
			doesCartItemMatchAttributes( cartItem, [
				{ attribute: 'Color', value: 'blue' },
			] )
		).toBe( false );
	} );

	it( 'returns false when selectedAttributes is not an array', () => {
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = makeCartItem( {
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			doesCartItemMatchAttributes(
				cartItem,
				undefined as unknown as SelectedAttributes[]
			)
		).toBe( false );
	} );

	it( 'returns false when the recorded and selected attribute counts differ', () => {
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = makeCartItem( {
			variation: [
				{
					attribute: 'Color',
					value: 'blue',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect( doesCartItemMatchAttributes( cartItem, [] ) ).toBe( false );
	} );

	it( 'matches directly when the recorded value equals the selected value, case-insensitively', () => {
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = makeCartItem( {
			variation: [
				{
					attribute: 'Color',
					value: 'green',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			doesCartItemMatchAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'GREEN' },
			] )
		).toBe( true );
	} );

	it( 'returns false when the recorded value does not equal the selected value', () => {
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = makeCartItem( {
			variation: [
				{
					attribute: 'Color',
					value: 'green',
					raw_attribute: 'attribute_pa_color',
				},
			],
		} );

		expect(
			doesCartItemMatchAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			] )
		).toBe( false );
	} );

	it( 'resolves the recorded term name to its slug via the parent product before comparing', () => {
		mockState = {
			products: {
				items: {
					10: {
						id: 10,
						type: 'variable',
						attributes: [
							{
								id: 1,
								name: 'Color',
								taxonomy: 'pa_color',
								has_variations: true,
								terms: [
									{ id: 1, name: 'Blue', slug: 'blue' },
								],
							},
						],
					} as unknown as ProductResponseItem,
				},
				variations: {
					20: { id: 20, parent: 10 } as ProductResponseItem,
				},
			},
		};
		const doesCartItemMatchAttributes = loadMatcher();
		// The recorded value is the term's display name ("Blue"); the
		// selected value is its slug ("blue") — the label/slug mismatch
		// this resolution reconciles.
		const cartItem = {
			id: 20,
			type: 'variation',
			quantity: 1,
			variation: [ { attribute: 'Color', value: 'Blue' } ],
		} as unknown as OptimisticCartItem;

		expect(
			doesCartItemMatchAttributes( cartItem, [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			] )
		).toBe( true );
	} );

	it( 'falls back to the raw recorded value when no parent product data is loaded', () => {
		// No product/variation data seeded at all — the parent lookup
		// resolves to nothing, so the fallback path (comparing the raw
		// recorded value against the selected value directly) is exercised.
		const doesCartItemMatchAttributes = loadMatcher();
		const cartItem = {
			id: 999,
			type: 'variation',
			quantity: 1,
			variation: [ { attribute: 'Color', value: 'blue' } ],
		} as unknown as OptimisticCartItem;

		expect(
			doesCartItemMatchAttributes( cartItem, [
				{ attribute: 'Color', value: 'blue' },
			] )
		).toBe( true );
	} );
} );
