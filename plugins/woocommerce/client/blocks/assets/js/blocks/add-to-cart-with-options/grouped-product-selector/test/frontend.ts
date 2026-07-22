/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { ProductsStoreState } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import type { Context as AddToCartWithOptionsStoreContext } from '../../frontend';

type MockStore = {
	state: Record< string, unknown >;
	actions: Record< string, ( ...args: unknown[] ) => unknown >;
	callbacks: Record< string, ( ...args: unknown[] ) => void >;
};

// The `woocommerce/add-to-cart-with-options` store slice this file
// registers (a single `store()` call).
let mockRegisteredStore: MockStore | null = null;

// The default (`woocommerce/add-to-cart-with-options`) context `getContext()`
// returns.
let mockContext: AddToCartWithOptionsStoreContext;

// The `woocommerce/products` store's state, consulted one-directionally.
let mockProductsState: Partial< ProductsStoreState >;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {} ) ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				if ( name === 'woocommerce/products' ) {
					return { state: mockProductsState };
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

/**
 * Loads a fresh copy of the grouped-product-selector frontend module so it
 * registers its store slice against the mocked `store()`, stubbing in the
 * `clearErrors`/`addError` actions that `validateGroupedProductQuantity`
 * calls — the real implementations live in the main `frontend.ts`, merged
 * onto the same namespace only when both modules load together in
 * production. The stubs reproduce their observable behavior (mutating
 * `mockContext.validationErrors`) so this file's own logic can be tested in
 * isolation.
 *
 * @return The registered store slice.
 */
function loadStore(): MockStore {
	mockRegisteredStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	const registeredStore = mockRegisteredStore as MockStore | null;
	if ( ! registeredStore ) {
		throw new Error( 'Grouped product selector store was not registered.' );
	}
	registeredStore.actions.clearErrors = ( ( group?: string ) => {
		mockContext.validationErrors = group
			? mockContext.validationErrors.filter(
					( error ) => error.group !== group
			  )
			: [];
	} ) as ( ...args: unknown[] ) => unknown;
	registeredStore.actions.addError = ( ( error: {
		code: string;
		group: string;
		message: string;
	} ) => {
		mockContext.validationErrors.push( error );
		return error.code;
	} ) as ( ...args: unknown[] ) => unknown;
	return registeredStore;
}

describe( 'Grouped product selector frontend store', () => {
	beforeEach( () => {
		mockContext = {
			selectedAttributes: [],
			quantity: {},
			validationErrors: [],
			tempQuantity: 0,
			groupedProductIds: [ 10, 11 ],
		};
		mockProductsState = { findProduct: jest.fn() };
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'no longer exposes a batchAddToCart action', () => {
		const { actions } = loadStore();

		expect( actions.batchAddToCart ).toBeUndefined();
	} );

	describe( 'validateGroupedProductQuantity', () => {
		it( 'adds a missing-items error when every child quantity is 0', () => {
			mockContext.quantity = { 10: 0, 11: 0 };

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity();

			expect(
				mockContext.validationErrors.some(
					( error ) =>
						error.code === 'groupedProductAddToCartMissingItems'
				)
			).toBe( true );
		} );

		it( 'adds an invalid-quantity error when a selected child is outside its min/max', () => {
			mockContext.quantity = { 10: 100, 11: 0 };
			( mockProductsState.findProduct as jest.Mock ).mockImplementation(
				( { id }: { id: number } ) =>
					( {
						id,
						add_to_cart: { minimum: 1, maximum: 5 },
					} as unknown as ProductResponseItem )
			);

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity();

			expect(
				mockContext.validationErrors.some(
					( error ) => error.code === 'invalidQuantities'
				)
			).toBe( true );
		} );

		it( 'clears errors when at least one child has a valid, in-range quantity', () => {
			mockContext.quantity = { 10: 2, 11: 0 };
			mockContext.validationErrors = [
				{
					code: 'invalidQuantities',
					group: 'invalid-quantities',
					message: 'stale error',
				},
			];
			( mockProductsState.findProduct as jest.Mock ).mockImplementation(
				( { id }: { id: number } ) =>
					( {
						id,
						add_to_cart: { minimum: 1, maximum: 5 },
					} as unknown as ProductResponseItem )
			);

			const { actions } = loadStore();
			actions.validateGroupedProductQuantity();

			expect( mockContext.validationErrors ).toEqual( [] );
		} );
	} );
} );
