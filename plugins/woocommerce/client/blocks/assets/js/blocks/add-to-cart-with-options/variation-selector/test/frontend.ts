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
	actions: Record< string, ( ...args: unknown[] ) => void >;
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

// The `woocommerce/cart` store's state this module reads and writes through
// (`itemInContext.draft`, the draft view). Setting `itemInContext.draft`
// simulates a draft view already resolved for the in-context product/
// variation — whether it answers a live draft's values, another surface's
// values, or a not-yet-materialized seed; an absent `draft` simulates no
// product in context. Because this file's tests never exercise the real
// draft view, `draft` is a directly-settable plain object standing in for
// it.
let mockCartState: { itemInContext: Envelope };

// The element `getElement()` returns for the currently-executing directive.
// Set per test to simulate the quantity input (`watchQuantityConstraints`
// runs on its own element) or left `null` for callbacks that don't read it.
let mockElementRef: HTMLElement | null;

// Spies for the sibling actions this module calls (`setQuantity`,
// `clearErrors`, `addError`) that are actually implemented in the main
// `add-to-cart-with-options/frontend.ts` module. In production, every
// `store()` call sharing the `woocommerce/add-to-cart-with-options`
// namespace merges into one object, so this module's own `actions` binding
// ends up including them; simulated here by merging these spies onto the
// registered store's own action definitions, since this test loads the
// variation-selector module in isolation.
let mockSetQuantity: jest.Mock;
let mockClearErrors: jest.Mock;
let mockAddError: jest.Mock;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {} ) ),
		getElement: jest.fn( () => ( { ref: mockElementRef } ) ),
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
					return { state: mockCartState };
				}
				mockRegisteredStore = {
					state: definition?.state ?? {},
					actions: {
						...( definition?.actions ?? {} ),
						setQuantity: mockSetQuantity,
						clearErrors: mockClearErrors,
						addError: mockAddError,
					},
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

/**
 * Builds a draft-view stand-in whose `variation` property is backed by a
 * spy setter, so a test can assert exactly how many times — and with what
 * value — a callback wrote `variation` through it.
 *
 * @param initial The draft's other, non-`variation` starting properties.
 * @return The draft object and the setter spy watching its `variation`
 *         property.
 */
function createVariationWriteSpy(
	initial: Partial< Envelope[ 'draft' ] > = {}
) {
	const setter = jest.fn();
	let current: unknown;
	const draft = { id: 1, ...initial } as NonNullable< Envelope[ 'draft' ] >;
	Object.defineProperty( draft, 'variation', {
		enumerable: true,
		configurable: true,
		get: () => current,
		set: ( value: unknown ) => {
			current = value;
			setter( value );
		},
	} );
	return { draft, setter };
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
		mockCartState = { itemInContext: {} };
		mockElementRef = null;
		mockSetQuantity = jest.fn();
		mockClearErrors = jest.fn();
		mockAddError = jest.fn();
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'no longer exposes the deleted reflection callback, guard action, or context member', () => {
		const { actions, callbacks } = loadStore();

		expect( callbacks.setSelectedVariationId ).toBeUndefined();
		expect( actions.removeAttribute ).toBeUndefined();
		expect( callbacks.resolveVariationId ).toBeInstanceOf( Function );
	} );

	describe( 'actions.toggle', () => {
		it( 'does nothing, and writes no draft, when the item is hidden or disabled', () => {
			const { draft, setter } = createVariationWriteSpy();
			mockCartState.itemInContext = { draft };
			mockContext.selectedAttributes = [];

			const { actions } = loadStore();
			actions.toggle( { id: 'blue', value: 'blue', hidden: true } );
			actions.toggle( { id: 'blue', value: 'blue', disabled: true } );

			expect( setter ).not.toHaveBeenCalled();
			expect( mockContext.selectedAttributes ).toEqual( [] );
		} );

		it( "records exactly one write to the resolved draft, sourced from this surface's local selection, when selecting a new value", () => {
			const { draft, setter } = createVariationWriteSpy();
			mockCartState.itemInContext = { draft };
			mockContext.name = 'Color';
			mockContext.selectedAttributes = [];
			mockContext.autoselect = false;

			const { actions } = loadStore();
			actions.toggle( { id: 'blue', value: 'blue', label: 'Blue' } );

			expect( setter ).toHaveBeenCalledTimes( 1 );
			expect( setter ).toHaveBeenCalledWith( [
				{ attribute: 'Color', value: 'blue' },
			] );
			expect( mockContext.selectedValue ).toBe( 'blue' );
		} );

		it( 'records exactly one write, replacing the shared selection wholesale, when deselecting the currently selected value', () => {
			const { draft, setter } = createVariationWriteSpy( {
				id: 2,
				quantity: 1,
			} );
			// The resolved collection already carries this exact selection —
			// `state.selectedAttributes` (draft-first) reports it as
			// currently selected — while this surface's own local context
			// mirrors the same selection, the state a genuine prior write
			// would have left it in.
			draft.variation = [ { attribute: 'Color', value: 'blue' } ];
			setter.mockClear();
			mockCartState.itemInContext = { draft };
			mockContext.name = 'Color';
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];

			const { actions } = loadStore();
			actions.toggle( { id: 'blue', value: 'blue', label: 'Blue' } );

			expect( setter ).toHaveBeenCalledTimes( 1 );
			expect( setter ).toHaveBeenCalledWith( [] );
			expect( mockContext.selectedValue ).toBe( '' );
		} );

		it( "includes the autoselectAttributes cascade's additional attribute in the single write", () => {
			const { draft, setter } = createVariationWriteSpy();
			mockCartState.itemInContext = { draft };
			mockContext.name = 'Color';
			mockContext.selectedAttributes = [];
			mockContext.autoselect = true;
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [
					{
						id: 2,
						attributes: [
							{ name: 'Color', value: 'blue' },
							{ name: 'Size', value: 'M' },
						],
					},
				],
			} as unknown as ProductResponseItem;

			const { actions } = loadStore();
			actions.toggle( { id: 'blue', value: 'blue', label: 'Blue' } );

			// Only one write happens, and it carries both the direct
			// selection and the cascade's autoselected "Size", proving the
			// write runs after both have landed on the local context.
			expect( setter ).toHaveBeenCalledTimes( 1 );
			expect( setter ).toHaveBeenCalledWith( [
				{ attribute: 'Color', value: 'blue' },
				{ attribute: 'Size', value: 'M' },
			] );
		} );

		it( 'writes no draft when no product is in context', () => {
			mockCartState.itemInContext = {};
			mockContext.name = 'Color';
			mockContext.selectedAttributes = [];

			const { actions } = loadStore();
			expect( () =>
				actions.toggle( { id: 'blue', value: 'blue', label: 'Blue' } )
			).not.toThrow();
			expect( mockContext.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'blue' },
			] );
		} );
	} );

	describe( 'callbacks.setDefaultSelectedAttribute', () => {
		it( 'writes no draft while resolving the configured default locally', () => {
			const { draft, setter } = createVariationWriteSpy();
			mockCartState.itemInContext = { draft };
			mockContext.name = 'Color';
			mockContext.selectedValue = 'blue';
			mockContext.autoselect = false;

			const { callbacks } = loadStore();
			callbacks.setDefaultSelectedAttribute();

			expect( mockContext.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'blue' },
			] );
			expect( setter ).not.toHaveBeenCalled();
		} );

		it( 'does nothing when the context declares no attribute name', () => {
			mockContext.name = '';

			const { callbacks } = loadStore();
			expect( () =>
				callbacks.setDefaultSelectedAttribute()
			).not.toThrow();
			expect( mockContext.selectedAttributes ).toEqual( [] );
		} );
	} );

	describe( 'callbacks.resolveVariationId', () => {
		it( 'does nothing when the base product has no variations', () => {
			mockProductsState.baseProductInContext = {
				id: 1,
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn();

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( mockProductsState.findProduct ).not.toHaveBeenCalled();
			expect( mockProductsState.variationId ).toBeUndefined();
		} );

		it( "resolves this surface's local selection into the global variationId when no shared draft exists yet", () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 }, { id: 3 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( mockProductsState.findProduct ).toHaveBeenCalledWith( {
				id: 1,
				selectedAttributes: mockContext.selectedAttributes,
			} );
			expect( mockProductsState.variationId ).toBe( 2 );
		} );

		it( 'writes the resolved variation id onto a per-element `woocommerce/products` context when present', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsContext = { variationId: null };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( mockProductsContext.variationId ).toBe( 2 );
			expect( mockProductsState.variationId ).toBeUndefined();
		} );

		it( 'resolves to null when the attributes do not fully resolve a variation', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			// findProduct returns the parent itself when no variation matches.
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( mockProductsState.variationId ).toBeNull();
		} );

		it( "reads the shared draft-first selection rather than this surface's own empty local one, once another surface's edit has resolved a family draft", () => {
			// This surface's own local selection was never touched
			// (`context.selectedAttributes` stays empty), but the resolved
			// collection already carries a real selection made on another
			// surface. `state.selectedAttributes` is draft-first, so this
			// resolver — like every other surface sharing the collection —
			// maps that same shared selection, with no bystander guard
			// needed to protect it.
			mockContext.selectedAttributes = [];
			mockCartState.itemInContext = {
				draft: {
					id: 2,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				},
			};
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( mockProductsState.findProduct ).toHaveBeenCalledWith( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );
			expect( mockProductsState.variationId ).toBe( 2 );
		} );

		it( 'is idempotent: repeated calls against an unchanged shared selection resolve the same variationId', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();
			expect( mockProductsState.variationId ).toBe( 2 );

			callbacks.resolveVariationId();
			expect( mockProductsState.variationId ).toBe( 2 );
		} );

		it( 'resolves to null once the selection is cleared back to empty, with no memory of a prior real selection', () => {
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();
			expect( mockProductsState.variationId ).toBe( 2 );

			mockContext.selectedAttributes = [];
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			callbacks.resolveVariationId();

			expect( mockProductsState.variationId ).toBeNull();
		} );

		it( 'writes no draft, even when the resolved collection already carries one', () => {
			const { draft, setter } = createVariationWriteSpy( {
				id: 2,
				quantity: 1,
			} );
			mockCartState.itemInContext = { draft };
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.resolveVariationId();

			expect( setter ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'callbacks.validateVariation', () => {
		it( 'does nothing beyond clearing errors when the base product has no variations', () => {
			mockProductsState.baseProductInContext = {
				id: 1,
			} as ProductResponseItem;

			const { callbacks } = loadStore();
			callbacks.validateVariation();

			expect( mockClearErrors ).toHaveBeenCalledWith(
				'variable-product'
			);
			expect( mockAddError ).not.toHaveBeenCalled();
		} );

		it( 'adds a missing-attributes error when neither the local selection nor the resolved collection draft resolves a variation', () => {
			mockContext.selectedAttributes = [];
			mockCartState.itemInContext = {};
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			// findProduct returns the parent itself when no variation matches.
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 1 } as ProductResponseItem )
			);

			const { callbacks } = loadStore();
			callbacks.validateVariation();

			expect( mockAddError ).toHaveBeenCalledWith(
				expect.objectContaining( {
					code: 'variableProductMissingAttributes',
					group: 'variable-product',
				} )
			);
		} );

		it( "validates the resolved collection's draft-resolved attribute selection rather than this instance's stale local context, so a sibling surface displaying a complete configuration can also submit it", () => {
			// This surface's own chips were never touched by the shopper
			// (local selection stays empty), but another surface sharing
			// that collection already fully resolved a variation;
			// `selectableItems` displays that draft selection as checked
			// (`state.selectedAttributes` is draft-backed), so validation
			// must resolve the same variation.
			mockContext.selectedAttributes = [];
			mockCartState.itemInContext = {
				draft: {
					id: 2,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				},
			};
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);
			mockProductsState.productVariations = {
				2: { is_in_stock: true } as ProductResponseItem,
			};

			const { callbacks } = loadStore();
			callbacks.validateVariation();

			expect( mockProductsState.findProduct ).toHaveBeenCalledWith( {
				id: 1,
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			} );
			expect( mockAddError ).not.toHaveBeenCalled();
		} );

		it( 'adds an out-of-stock error when the draft-resolved variation is out of stock', () => {
			mockContext.selectedAttributes = [];
			mockCartState.itemInContext = {
				draft: {
					id: 2,
					quantity: 1,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				},
			};
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 2 } as ProductResponseItem )
			);
			mockProductsState.productVariations = {
				2: { is_in_stock: false } as ProductResponseItem,
			};

			const { callbacks } = loadStore();
			callbacks.validateVariation();

			expect( mockAddError ).toHaveBeenCalledWith(
				expect.objectContaining( {
					code: 'variableProductOutOfStock',
					group: 'variable-product',
				} )
			);
		} );
	} );

	describe( 'callbacks.watchQuantityConstraints', () => {
		/**
		 * Builds a detached quantity `<input>` for `getElement().ref`.
		 *
		 * @param value The input's initial `value`.
		 * @return The created input element.
		 */
		function createQuantityInput( value: number ): HTMLInputElement {
			const input = document.createElement( 'input' );
			input.type = 'number';
			input.value = String( value );
			return input;
		}

		it( 'does nothing when there is no resolved variation', () => {
			mockElementRef = createQuantityInput( 1 );
			mockProductsState.productVariationInContext = null;

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).not.toHaveBeenCalled();
		} );

		it( 'does nothing when the draft view answers no numeric quantity yet, never falling back to a local quantity map', () => {
			mockElementRef = createQuantityInput( 5 );
			mockProductsState.productVariationInContext = {
				id: 25,
				add_to_cart: { minimum: 1, maximum: 3 },
			} as ProductResponseItem;
			mockCartState.itemInContext = {};

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).not.toHaveBeenCalled();
		} );

		it( "reads the shared draft's own quantity, never this surface's local quantity map", () => {
			// The shopper set quantity 3 on another surface, which resolved
			// the variation and wrote its draft; this surface's own local
			// `quantity` context still holds the never-touched default of
			// 1, which must play no part in the clamp decision.
			mockElementRef = createQuantityInput( 3 );
			mockProductsState.productVariationInContext = {
				id: 25,
				add_to_cart: { minimum: 1, maximum: 10 },
			} as ProductResponseItem;
			mockContext.quantity = { 25: 1 };
			mockCartState.itemInContext = {
				draft: { id: 25, quantity: 3 },
			};

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).not.toHaveBeenCalled();
		} );

		it( "clamps the shared draft's own quantity to the resolved variation's maximum when it is out of bounds", () => {
			mockElementRef = createQuantityInput( 5 );
			mockProductsState.productVariationInContext = {
				id: 25,
				add_to_cart: { minimum: 1, maximum: 3 },
			} as ProductResponseItem;
			mockCartState.itemInContext = {
				draft: { id: 25, quantity: 5 },
			};

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 25, 3 );
		} );

		it( "clamps an untouched surface's seeded quantity, through the draft view, to the resolved variation's minimum", () => {
			// An untouched surface's draft view answers its seed's quantity
			// (0, below this variation's minimum) pre-materialization;
			// `setQuantity` is the write that goes on to materialize it.
			mockElementRef = createQuantityInput( 0 );
			mockProductsState.productVariationInContext = {
				id: 25,
				add_to_cart: { minimum: 1, maximum: 10 },
			} as ProductResponseItem;
			mockCartState.itemInContext = {
				draft: { id: 25, quantity: 0 },
			};

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 25, 1 );
		} );

		it( 'does nothing when the resolved value already matches both the input and the constraint bounds', () => {
			mockElementRef = createQuantityInput( 2 );
			mockProductsState.productVariationInContext = {
				id: 25,
				add_to_cart: { minimum: 1, maximum: 3 },
			} as ProductResponseItem;
			mockCartState.itemInContext = {
				draft: { id: 25, quantity: 2 },
			};

			const { callbacks } = loadStore();
			callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'state.selectedAttributes', () => {
		it( "displays another surface's draft update for the same resolved collection, not this instance's stale local selection", () => {
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

		it( 'falls back to the local selection when the resolved collection holds no draft variation (including one belonging to a different collection)', () => {
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

		it( "falls back to the local selection — never an empty array — when the resolved draft's `variation` is present but empty", () => {
			// The draft view always answers `variation` as a real array:
			// an unconfigured draft reads as `[]`, which is truthy. Judging
			// emptiness by length (not truthiness) is what stops this empty
			// array from permanently shadowing this surface's own default.
			mockContext.selectedAttributes = [
				{ attribute: 'Color', value: 'blue' },
			];
			mockCartState.itemInContext = {
				draft: { id: 1, quantity: 1, variation: [] },
			};

			const { state } = loadStore();

			expect( state.selectedAttributes ).toEqual( [
				{ attribute: 'Color', value: 'blue' },
			] );
		} );
	} );
} );
