/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';
import type { Envelope } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { Context as AddToCartWithOptionsStoreContext } from '../../frontend';

type Context = AddToCartWithOptionsStoreContext & {
	name: string;
	selectedValue: string | null;
	variationAttributeOptions: unknown[];
	autoselect: boolean;
};

type MockStore = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, ( ...args: unknown[] ) => void >;
};

// The `woocommerce/add-to-cart-with-options` store the variation selector
// registers against (a single `store()` call in this file, unlike the main
// frontend.ts's two-call pattern).
let mockRegisteredStore: MockStore | null = null;

// The default (`woocommerce/add-to-cart-with-options`) context `getContext()`
// returns.
let mockContext: Context;

// The `woocommerce/products` context `getContext( 'woocommerce/products' )`
// returns — `undefined` simulates no per-element override (falls back to
// updating `productsState.variationId` directly).
let mockProductsContext: { variationId?: number | null } | undefined;

// The `woocommerce/products` store's state, consulted one-directionally.
let mockProductsState: Partial< ProductsStoreState >;

// The `woocommerce/cart` store's `upsertDraftItem` spy, controlled per test.
let mockUpsertDraftItem: jest.Mock;

// The `woocommerce/cart` store's state this module reads (`itemInContext`).
// Setting `itemInContext.draft` simulates a draft already present in the
// current scope — whether written by this same surface or by another
// surface sharing the scope; an empty envelope simulates no draft for the
// in-context product (including one belonging to a different scope, which
// `itemInContext` would never surface here).
let mockCartState: { itemInContext: Envelope };

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {} ) ),
		getElement: jest.fn( () => ( { ref: null } ) ),
		getContext: jest.fn( ( namespace?: string ) => {
			if ( namespace === 'woocommerce/products' ) {
				return mockProductsContext;
			}
			return mockContext;
		} ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
				}
				if ( name === 'woocommerce/cart' ) {
					return {
						state: mockCartState,
						actions: { upsertDraftItem: mockUpsertDraftItem },
					};
				}
				mockRegisteredStore = {
					state: definition?.state ?? {},
					actions: definition?.actions ?? {},
					callbacks: definition?.callbacks ?? {},
				} as MockStore;
				return mockRegisteredStore;
			}
		),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );

/**
 * Loads a fresh copy of the variation-selector frontend module so it
 * registers its store slice against the mocked `store()`.
 *
 * @return The registered store slice.
 */
function loadStore(): MockStore {
	mockRegisteredStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Variation selector store was not registered.' );
	}
	return mockRegisteredStore;
}

describe( 'Variation selector frontend store', () => {
	beforeEach( () => {
		mockContext = {
			selectedAttributes: [],
			quantity: {},
			validationErrors: [],
			tempQuantity: 0,
			groupedProductIds: [],
			name: '',
			selectedValue: null,
			variationAttributeOptions: [],
			autoselect: false,
		};
		mockProductsContext = undefined;
		mockProductsState = {};
		mockUpsertDraftItem = jest.fn();
		mockCartState = { itemInContext: {} };
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'callbacks.setSelectedVariationId', () => {
		it( 'does nothing when the base product has no variations', () => {
			mockProductsState.baseProductInContext = {
				id: 1,
			} as ProductResponseItem;

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockUpsertDraftItem ).not.toHaveBeenCalled();
		} );

		it( 'upserts the resolved variation draft with quantity + variation when attributes fully match', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockContext.quantity = { 1: 1, 2: 1, 3: 1 };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 }, { id: 3 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() =>
					( {
						id: 2,
					} as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				},
				{ id: 2 }
			);
			// The resolved variation id is written directly onto the
			// products store state, since no per-element context overrides.
			expect( mockProductsState.variationId ).toBe( 2 );
		} );

		it( 'writes the resolved variation id onto a per-element `woocommerce/products` context when present', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsContext = { variationId: null };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() =>
					( {
						id: 2,
					} as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockProductsContext.variationId ).toBe( 2 );
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 1, variation: mockContext.selectedAttributes },
				{ id: 2 }
			);
		} );

		it( 'upserts the base product draft when attributes do not fully resolve a variation', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			// findProduct returns the parent itself when no variation matches.
			mockProductsState.findProduct = jest.fn(
				() =>
					( {
						id: 1,
					} as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockProductsState.variationId ).toBeNull();
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 1, variation: mockContext.selectedAttributes },
				{ id: 1 }
			);
		} );

		it( 'still seeds the unconfigured draft when nothing has resolved a variation yet', () => {
			// No attribute selection of its own, and nothing else has
			// resolved a variation yet (the fresh-page-load case): the
			// initial write establishing the "nothing selected" baseline
			// must still happen, exactly as today.
			mockContext.selectedAttributes = [];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsState.variationId = null;
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockProductsState.variationId ).toBeNull();
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 1, variation: [] },
				{ id: 1 }
			);
		} );

		it( "does not clobber another surface's already-resolved global variation when this surface has no attribute selection of its own", () => {
			// Simulates a second, never-configured page-wide surface whose
			// watch re-runs (e.g. triggered by unrelated variation data
			// finishing an async load) after another surface has already
			// resolved a variation into the shared global pointer.
			mockContext.selectedAttributes = [];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsState.variationId = 2;
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockUpsertDraftItem ).not.toHaveBeenCalled();
			expect( mockProductsState.variationId ).toBe( 2 );
		} );

		it( "does not clobber another surface's already-resolved per-element variation when this surface has no attribute selection of its own", () => {
			mockContext.selectedAttributes = [];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsContext = { variationId: 2 };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.setSelectedVariationId();

			expect( mockUpsertDraftItem ).not.toHaveBeenCalled();
			expect( mockProductsContext.variationId ).toBe( 2 );
		} );

		it( 'keeps writing after a real selection made on this surface is cleared back to empty', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockContext.quantity = { 1: 1, 2: 1 };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			// A real selection made on this surface resolves and writes,
			// exactly as today.
			callbacks.setSelectedVariationId();
			expect( mockProductsState.variationId ).toBe( 2 );

			// The shopper then clears the selection back to empty, on the
			// same surface: this is a genuine, local edit, not another
			// surface's stale re-evaluation, so it must still write through
			// and reset the shared pointer/draft, unlike the never-selected
			// bystander case above.
			mockContext.selectedAttributes = [];
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);
			mockUpsertDraftItem.mockClear();

			callbacks.setSelectedVariationId();

			expect( mockProductsState.variationId ).toBeNull();
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 1, variation: [] },
				{ id: 1 }
			);
		} );
	} );

	describe( 'state.selectedAttributes', () => {
		it( "displays another surface's draft update for the same scope, not this instance's stale local selection", () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockCartState.itemInContext = {
				draft: {
					id: 2,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'red' } ],
				},
			};

			const { state } = loadStore();

			expect( state.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'red' },
			] );
		} );

		it( 'falls back to the local selection when the scope holds no draft variation (including one belonging to a different scope)', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockCartState.itemInContext = {};

			const { state } = loadStore();

			expect( state.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'blue' },
			] );
		} );

		it( "falls back to the local selection when the resolved draft carries no `variation` (e.g. a simple product's draft)", () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockCartState.itemInContext = {
				draft: { id: 1, quantity: 1 },
			};

			const { state } = loadStore();

			expect( state.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'blue' },
			] );
		} );
	} );
} );
