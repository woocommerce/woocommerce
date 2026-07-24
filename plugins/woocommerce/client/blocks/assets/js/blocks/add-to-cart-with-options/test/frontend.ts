/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

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

// The unified `woocommerce` store's state, consulted one-directionally.
// `itemInContext.product` backs the context-aware reads (`allowsAddingToCart`,
// `validateQuantity`, `addToCart`); `products.productId`/`.items` back the
// page-level (context-*un*aware) grouped check in `setQuantity` — see that
// test's own comment for why the check must stay non-contextual.
let mockWooState: {
	itemInContext: {
		product: ProductResponseItem | null;
	};
	products: {
		productId: number;
		items: Record< number, ProductResponseItem >;
	};
	findItem: jest.Mock;
};

// The `woocommerce` store's action spies, controlled per test.
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
				if ( name === 'woocommerce' ) {
					return {
						state: mockWooState,
						actions: {
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
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
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
		mockWooState = {
			itemInContext: { product: null },
			products: { productId: 0, items: {} },
			findItem: jest.fn( ( args?: { id?: number } ) => ( {
				draftItem: { id: args?.id },
			} ) ),
		};
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
		it( 'writes exactly one draft write via findItem({ id }).draftItem.quantity for a simple product', () => {
			mockContext.quantity = { 42: 1 };
			mockWooState.products = {
				productId: 42,
				items: {
					42: { id: 42, type: 'simple' } as ProductResponseItem,
				},
			};

			const { actions } = loadStore();
			actions.setQuantity( 42, 3 );

			expect( mockWooState.findItem ).toHaveBeenCalledTimes( 1 );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 42 } );
			const { draftItem } = mockWooState.findItem.mock.results[ 0 ].value;
			expect( draftItem.quantity ).toBe( 3 );
		} );

		it( 'drafts only the edited id, not sibling variation ids, for a variable product', () => {
			mockContext.quantity = { 1: 1, 2: 1, 3: 1 };
			mockWooState.products = {
				productId: 1,
				items: {
					1: {
						id: 1,
						type: 'variable',
						variations: [ { id: 2 }, { id: 3 } ],
					} as ProductResponseItem,
				},
			};

			const { actions } = loadStore();
			actions.setQuantity( 2, 5 );

			// The per-variation local fan-out is gone: the local context
			// write now only ever targets the id the shopper actually
			// edited. Siblings (1, 3) keep whatever local value they
			// already had — the shared family draft (not this local map)
			// is what carries the quantity across a variation switch.
			expect( mockContext.quantity ).toEqual( { 1: 1, 2: 5, 3: 1 } );

			// The draft write, too, is only ever made for the id the
			// shopper actually edited — never every sibling variation.
			expect( mockWooState.findItem ).toHaveBeenCalledTimes( 1 );
			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 2 } );
			const { draftItem } = mockWooState.findItem.mock.results[ 0 ].value;
			expect( draftItem.quantity ).toBe( 5 );
		} );

		it( 'writes the draft for a grouped product child row', () => {
			// A grouped-product child row resolves `itemInContext.product`
			// to the child (its own nested `woocommerce` context override —
			// see `GroupedProductItemSelector::get_quantity_selector_markup`),
			// while `products.productId`/`.items` — the page-level,
			// context-*un*aware pair — still names the grouped parent.
			mockContext.quantity = { 20: 0, 21: 0 };
			mockWooState.products = {
				productId: 10,
				items: {
					10: { id: 10, type: 'grouped' } as ProductResponseItem,
				},
			};

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity = jest.fn();
			actions.setQuantity( 20, 2 );

			expect( mockWooState.findItem ).toHaveBeenCalledWith( { id: 20 } );
			const { draftItem } = mockWooState.findItem.mock.results[ 0 ].value;
			expect( draftItem.quantity ).toBe( 2 );
			expect( actions.validateGroupedProductQuantity ).toHaveBeenCalled();
		} );

		it( 'routes a grouped child edit through whole-group validation even when the in-context envelope resolves to the child itself', () => {
			// Pins the regression a context-aware grouped check would
			// reintroduce: the child row's own `woocommerce` context
			// override (`productId` = the child's id) makes
			// `itemInContext.product` resolve to the CHILD's own
			// (non-grouped) product — exactly as it does in production —
			// while the page-level `products.productId`/`.items` pair still
			// names the grouped parent. The grouped check must read the
			// latter, never the former, or every child edit after the
			// initial page load would silently skip whole-group validation
			// (the "at least one selected" / all-children-in-range check).
			mockContext.quantity = { 20: 0, 21: 0 };
			mockWooState.itemInContext.product = {
				id: 20,
				type: 'simple',
			} as ProductResponseItem;
			mockWooState.products = {
				productId: 10,
				items: {
					10: { id: 10, type: 'grouped' } as ProductResponseItem,
				},
			};

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity = jest.fn();
			actions.validateQuantity = jest.fn();
			actions.setQuantity( 20, 2 );

			expect( actions.validateGroupedProductQuantity ).toHaveBeenCalled();
			expect( actions.validateQuantity ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'addToCart', () => {
		it( 'posts the in-context simple/variable product via addItem(), never addCartItem', async () => {
			mockContext.validationErrors = [];
			mockWooState.itemInContext.product = {
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
			mockWooState.itemInContext.product = {
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
			mockWooState.itemInContext.product = null;

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
			mockWooState.itemInContext.product = {
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
