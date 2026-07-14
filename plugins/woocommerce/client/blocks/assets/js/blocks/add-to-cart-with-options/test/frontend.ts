/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { Context, AddToCartWithOptionsStore } from '../frontend';

type MockStore = {
	state: AddToCartWithOptionsStore[ 'state' ] & Record< string, unknown >;
	actions: AddToCartWithOptionsStore[ 'actions' ] & Record< string, unknown >;
};

// The `woocommerce/add-to-cart-with-options` store `frontend.ts` registers.
// `frontend.ts` calls `store()` for this namespace twice — once empty (to
// obtain a stable `state` reference before the definition exists) and once
// with the full definition — so the mock below merges both calls onto the
// same persistent object, mirroring the real Interactivity runtime.
let mockRegisteredStore: MockStore | null = null;

// The shared `woocommerce/add-to-cart-with-options` context `getContext()`
// returns; each test seeds exactly the fields `setQuantity`/`addToCart` read.
let mockContext: Context;

// The `woocommerce/add-to-cart-with-options-quantity-selector` context,
// read by `setQuantity` for the input element to dispatch a change event on.
let mockQuantitySelectorContext: { inputElement?: HTMLInputElement | null };

// The `woocommerce/products` store's state, consulted one-directionally;
// tests set `productInContext`/`baseProductInContext`/`findProduct` directly
// rather than exercising the real `woocommerce/products` getters.
let mockProductsState: Partial< ProductsStoreState >;

// The `woocommerce/cart` store's action spies, controlled per test.
let mockUpsertDraftItem: jest.Mock;
let mockAddItem: jest.Mock;
let mockAddCartItem: jest.Mock;
let mockBatchAddCartItems: jest.Mock;

// The `woocommerce/store-notices` store's action spies, exercised only by
// the invalid-form branch.
let mockAddNotice: jest.Mock;
let mockRemoveNotice: jest.Mock;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {} ) ),
		getContext: jest.fn( ( namespace?: string ) => {
			if (
				namespace ===
				'woocommerce/add-to-cart-with-options-quantity-selector'
			) {
				return mockQuantitySelectorContext;
			}
			return mockContext;
		} ),
		withSyncEvent: ( fn: unknown ) => fn,
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
				}
				if ( name === 'woocommerce/cart' ) {
					return {
						actions: {
							upsertDraftItem: mockUpsertDraftItem,
							addItem: mockAddItem,
							addCartItem: mockAddCartItem,
							batchAddCartItems: mockBatchAddCartItems,
						},
					};
				}
				if ( name === 'woocommerce/store-notices' ) {
					return {
						actions: {
							addNotice: mockAddNotice,
							removeNotice: mockRemoveNotice,
						},
					};
				}
				if ( ! mockRegisteredStore ) {
					mockRegisteredStore = {
						state: {},
						actions: {},
					} as MockStore;
				}
				if ( definition?.state ) {
					Object.defineProperties(
						mockRegisteredStore.state,
						Object.getOwnPropertyDescriptors( definition.state )
					);
				}
				if ( definition?.actions ) {
					Object.assign(
						mockRegisteredStore.actions,
						definition.actions
					);
				}
				return mockRegisteredStore;
			}
		),
	} ),
	{ virtual: true }
);

// Side-effect store registrations `frontend.ts` imports for ordering only;
// the mocked `store()` above handles the registration calls directly.
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ), {
	virtual: true,
} );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives an async action.
 *
 * @param action The async action return value, treated as a generator.
 * @return A promise resolving once the generator finishes.
 */
async function runAction( action: unknown ): Promise< void > {
	const iterator = action as Iterator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		// eslint-disable-next-line no-await-in-loop
		const resolved = await next.value;
		next = iterator.next( resolved );
	}
}

/**
 * Loads a fresh copy of the add-to-cart-with-options frontend module so it
 * registers its block store against the mocked `store()` and exposes its
 * state/actions.
 *
 * @return The registered store.
 */
function loadStore(): MockStore {
	mockRegisteredStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Add to Cart + Options store was not registered.' );
	}
	return mockRegisteredStore;
}

/**
 * Builds a minimal fake submit event for `addToCart`.
 *
 * @return A fake `SubmitEvent` with a spy `preventDefault`.
 */
function makeSubmitEvent(): SubmitEvent {
	return { preventDefault: jest.fn() } as unknown as SubmitEvent;
}

describe( 'Add to Cart + Options frontend store', () => {
	beforeEach( () => {
		mockContext = {
			selectedAttributes: [],
			quantity: {},
			validationErrors: [],
			tempQuantity: 0,
			groupedProductIds: [],
		};
		mockQuantitySelectorContext = {};
		mockProductsState = {};
		mockUpsertDraftItem = jest.fn();
		mockAddItem = jest.fn( () => Promise.resolve() );
		mockAddCartItem = jest.fn( () => Promise.resolve() );
		mockBatchAddCartItems = jest.fn( () => Promise.resolve() );
		mockAddNotice = jest.fn( () => 'notice-id' );
		mockRemoveNotice = jest.fn();
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'setQuantity', () => {
		it( 'upserts the current-scope draft with the new quantity for a simple product', () => {
			mockContext.quantity = { 42: 1 };
			mockProductsState.baseProductInContext = {
				id: 42,
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() => ( { id: 42, type: 'simple' } as ProductResponseItem )
			);

			const { actions } = loadStore();
			actions.setQuantity( 42, 3 );

			expect( mockUpsertDraftItem ).toHaveBeenCalledTimes( 1 );
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 3 },
				{ id: 42 }
			);
		} );

		it( 'drafts only the edited id, not sibling variation ids, for a variable product', () => {
			mockContext.quantity = { 1: 1, 2: 1, 3: 1 };
			mockProductsState.baseProductInContext = {
				id: 1,
				variations: [ { id: 2 }, { id: 3 } ],
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn( () => null );

			const { actions } = loadStore();
			actions.setQuantity( 2, 5 );

			// Existing behavior preserved: local context quantity stays in
			// sync across every sibling variation id, so the quantity
			// persists when the shopper switches variations. The base
			// product's own id (1) is untouched — it was never part of the
			// sibling-variation sync (unchanged pre-existing behavior).
			expect( mockContext.quantity ).toEqual( { 1: 1, 2: 5, 3: 5 } );

			// The cart draft, however, is only ever written for the id the
			// shopper actually edited — the id currently resolved as
			// `productInContext` — not every sibling variation.
			expect( mockUpsertDraftItem ).toHaveBeenCalledTimes( 1 );
			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 5 },
				{ id: 2 }
			);
		} );

		it( 'upserts the draft for a grouped product child row', () => {
			// A grouped-product child row resolves `productInContext` to the
			// child (its own nested `woocommerce/products` context), and the
			// parent lookup used for validation resolves to the grouped
			// parent — registered separately by the grouped-product-selector
			// module, stubbed here since this test loads only `../frontend`.
			mockContext.quantity = { 20: 0, 21: 0 };
			mockProductsState.baseProductInContext = {
				id: 20,
			} as ProductResponseItem;
			mockProductsState.findProduct = jest.fn(
				() =>
					( {
						id: 10,
						type: 'grouped',
					} as ProductResponseItem )
			);

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity = jest.fn();
			actions.setQuantity( 20, 2 );

			expect( mockUpsertDraftItem ).toHaveBeenCalledWith(
				{ quantity: 2 },
				{ id: 20 }
			);
			expect( actions.validateGroupedProductQuantity ).toHaveBeenCalled();
		} );
	} );

	describe( 'addToCart', () => {
		it( 'posts the in-context simple/variable product via addItem(), never addCartItem', async () => {
			mockContext.validationErrors = [];
			mockProductsState.productInContext = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;

			const { actions } = loadStore();
			const event = makeSubmitEvent();
			await runAction( actions.addToCart( event ) );

			expect( event.preventDefault ).toHaveBeenCalled();
			expect( mockAddItem ).toHaveBeenCalledTimes( 1 );
			expect( mockAddItem ).toHaveBeenCalledWith();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
			expect( mockBatchAddCartItems ).not.toHaveBeenCalled();
		} );

		it( 'posts a grouped product via addItem() too, with no special-casing', async () => {
			mockContext.validationErrors = [];
			mockProductsState.productInContext = {
				id: 10,
				type: 'grouped',
			} as ProductResponseItem;

			const { actions } = loadStore();
			await runAction( actions.addToCart( makeSubmitEvent() ) );

			expect( mockAddItem ).toHaveBeenCalledTimes( 1 );
			expect( mockAddItem ).toHaveBeenCalledWith();
			expect( mockBatchAddCartItems ).not.toHaveBeenCalled();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
		} );

		it( 'does nothing when no product is in context', async () => {
			mockContext.validationErrors = [];
			mockProductsState.productInContext = null;

			const { actions } = loadStore();
			await runAction( actions.addToCart( makeSubmitEvent() ) );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
			expect( mockBatchAddCartItems ).not.toHaveBeenCalled();
		} );

		it( 'never calls addItem when the form is invalid', async () => {
			mockContext.validationErrors = [
				{
					code: 'invalidQuantities',
					group: 'invalid-quantities',
					message: 'Please select a valid quantity.',
				},
			];
			mockProductsState.productInContext = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;

			const { actions } = loadStore();
			await runAction( actions.addToCart( makeSubmitEvent() ) );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockAddCartItem ).not.toHaveBeenCalled();
			expect( mockBatchAddCartItems ).not.toHaveBeenCalled();
		} );
	} );
} );
