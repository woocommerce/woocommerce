/**
 * External dependencies
 */
import type { AddCartItemResult } from '@woocommerce/stores/woocommerce/cart';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { Context as AddToCartWithOptionsStoreContext } from '../../frontend';
import type { GroupedProductAddToCartWithOptionsStore } from '../frontend';

// `frontend.ts` registers its actions under the shared
// `woocommerce/add-to-cart-with-options` namespace, and opens the
// `woocommerce` cart store, the `woocommerce/products` store, and (only when
// a child is rejected) the `woocommerce/store-notices` store, all routed
// through the mocked `store()`.

// Context the mocked `getContext` returns for the grouped selector under test.
let mockContext: AddToCartWithOptionsStoreContext;

// The products store's lookup, controlled per test: returns a product (with
// `type`) for ids that resolve, or `undefined` for ids that don't.
let mockFindProduct: jest.Mock;

// The cart store's `addCartItem` spy, controlled per test to resolve
// `{ success: true }` or `{ success: false, error }` per item.
let mockAddCartItem: jest.Mock;

// The store-notices store's `addNotice` spy.
let mockAddNotice: jest.Mock;

// The cart store module's exported `emitSyncEvent`, captured via the
// consumer's dynamic `import( '@woocommerce/stores/woocommerce/cart' )`.
let mockEmitSyncEvent: jest.Mock;

// The Interactivity API's `getConfig`, controlled per test so the
// announcement path can be exercised both with and without config text.
let mockGetConfig: jest.Mock;

// The legacy "added to cart" event dispatcher.
let mockTriggerAddedToCartEvent: jest.Mock;

// The `@wordpress/a11y` screen-reader announcer.
let mockSpeak: jest.Mock;

// The block store's registered actions, populated when `frontend.ts` calls
// the mocked `store()` for the `woocommerce/add-to-cart-with-options`
// namespace.
let mockBlockActions:
	| GroupedProductAddToCartWithOptionsStore[ 'actions' ]
	| null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: ( ...args: unknown[] ) => mockGetConfig( ...args ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/add-to-cart-with-options' ) {
				mockBlockActions = definition?.actions ?? null;
				return {
					state: definition?.state,
					actions: definition?.actions,
				};
			}
			if ( name === 'woocommerce' ) {
				return { actions: { addCartItem: mockAddCartItem } };
			}
			if ( name === 'woocommerce/store-notices' ) {
				return { actions: { addNotice: mockAddNotice } };
			}
			// woocommerce/products
			return {
				state: {
					findProduct: ( ...args: unknown[] ) =>
						mockFindProduct( ...args ),
				},
			};
		} ),
	} ),
	{ virtual: true }
);

// The cart store's runtime-exported `emitSyncEvent`, reached via the
// consumer's dynamic import of this module. `addCartItem` itself is reached
// through the `store()` mock above, mirroring how the real cart store is
// private and only reachable via `store()`.
jest.mock(
	'@woocommerce/stores/woocommerce/cart',
	() => ( {
		emitSyncEvent: ( ...args: unknown[] ) => mockEmitSyncEvent( ...args ),
	} ),
	{ virtual: true }
);

// Side-effect store registration `frontend.ts` imports for ordering only.
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

// The consumer dynamically imports this module only for its registration
// side effect before reading `store-notices` actions via `store()`.
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ), {
	virtual: true,
} );

jest.mock( '../../../../base/stores/woocommerce/legacy-events', () => ( {
	triggerAddedToCartEvent: ( ...args: unknown[] ) =>
		mockTriggerAddedToCartEvent( ...args ),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: ( ...args: unknown[] ) => mockSpeak( ...args ),
} ) );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives `*batchAddToCart`.
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
 * Builds a minimal resolved product for `findProduct`.
 *
 * @param overrides Partial fields overriding the defaults.
 * @return A product response item exposing only the fields `batchAddToCart` reads.
 */
function makeProduct(
	overrides: Partial< ProductResponseItem > = {}
): ProductResponseItem {
	return {
		type: 'simple',
		...overrides,
	} as ProductResponseItem;
}

/**
 * Builds the grouped selector context for the row under test.
 *
 * @param overrides Partial fields overriding the defaults.
 * @return A context object suitable for the mocked `getContext`.
 */
function makeContext(
	overrides: Partial< AddToCartWithOptionsStoreContext > = {}
): AddToCartWithOptionsStoreContext {
	return {
		selectedAttributes: [],
		quantity: {},
		validationErrors: [],
		tempQuantity: 0,
		groupedProductIds: [],
		...overrides,
	};
}

/**
 * Builds a successful `addCartItem` resolution.
 *
 * @return A successful {@link AddCartItemResult}.
 */
function success(): AddCartItemResult {
	return { success: true };
}

/**
 * Builds a rejected `addCartItem` resolution carrying a raw server message.
 *
 * @param message The raw server-provided error message.
 * @return A failed {@link AddCartItemResult}.
 */
function failure( message: string ): AddCartItemResult {
	return { success: false, error: new Error( message ) };
}

/**
 * Loads a fresh copy of the grouped-product frontend module so it registers
 * its actions against the mocked `store()` and exposes them.
 *
 * @return The registered block-store actions.
 */
function loadBlockStore(): GroupedProductAddToCartWithOptionsStore[ 'actions' ] {
	mockBlockActions = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockBlockActions ) {
		throw new Error( 'Grouped product store was not registered.' );
	}
	return mockBlockActions;
}

describe( 'Grouped product batchAddToCart', () => {
	beforeEach( () => {
		mockContext = makeContext();
		mockFindProduct = jest.fn( () => undefined );
		mockAddCartItem = jest.fn( () => Promise.resolve( success() ) );
		mockAddNotice = jest.fn();
		mockEmitSyncEvent = jest.fn();
		mockTriggerAddedToCartEvent = jest.fn();
		mockSpeak = jest.fn();
		// Test writers that care about the announcement path override this
		// per test; a bare mock would otherwise route "success" through a
		// missing-config no-op, per the same gotcha `cart.ts`'s own tests
		// document.
		mockGetConfig = jest.fn( () => ( {
			messages: { addedToCartText: 'Item added to your cart.' },
		} ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'adds one addCartItem per selected child, skipping zero-quantity and unresolved children, with the suppression options and the delta quantity', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2, 3 ],
			quantity: { 1: 0, 2: 2, 3: 5 },
			selectedAttributes: [],
		} );
		// Id 1 is skipped for zero quantity before `findProduct` is even
		// consulted. Id 2 resolves; id 3 does not resolve to a product.
		mockFindProduct = jest.fn( ( { id } ) =>
			id === 2 ? makeProduct( { type: 'simple' } ) : undefined
		);

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockAddCartItem ).toHaveBeenCalledTimes( 1 );
		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{
				id: 2,
				quantityToAdd: 2,
				variation: [],
				type: 'simple',
			},
			{
				showCartUpdatesNotices: false,
				suppressPostAddSideEffects: true,
			}
		);
	} );

	it( 'issues every addCartItem call synchronously, before awaiting them together, so a single tick is shared by all submits', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2 ],
			quantity: { 1: 2, 2: 3 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		// Never-resolving promises: if `addCartItem` were awaited
		// individually between calls (sequential `yield`s), the second call
		// would not happen before this test's synchronous assertion runs.
		// Leaving them pending is sufficient to prove concurrency.
		mockAddCartItem = jest.fn( () => new Promise( () => undefined ) );

		const actions = loadBlockStore();
		const generator = actions.batchAddToCart() as unknown as Generator<
			unknown,
			void,
			unknown
		>;

		// First yield: the dynamic import capturing `emitSyncEvent`. Await
		// its (mocked) resolution and feed it back, exactly as the real
		// runtime driver would.
		const first = generator.next();
		const resolvedCartModule = await first.value;

		// Resuming past that yield runs the addedItems build and the
		// `.map()` synchronously, with no further yield in between, so both
		// addCartItem calls have already happened by the time the generator
		// suspends again at `Promise.all`.
		generator.next( resolvedCartModule );

		expect( mockAddCartItem ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'fires the legacy event, the sync event, and the announcement exactly once when at least one child succeeds, even with a mix of accepted and rejected children', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2, 3 ],
			quantity: { 1: 1, 2: 1, 3: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockAddCartItem = jest.fn( ( item ) => {
			if ( item.id === 2 ) {
				return Promise.resolve( failure( 'Child 2 is out of stock.' ) );
			}
			return Promise.resolve( success() );
		} );

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockTriggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockTriggerAddedToCartEvent ).toHaveBeenCalledWith( {
			preserveCartData: true,
		} );
		expect( mockEmitSyncEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledTimes( 1 );
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Item added to your cart.',
			'polite'
		);
	} );

	it( 'carries all selected child ids — accepted and rejected — in the single sync event payload', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 10, 20, 30 ],
			quantity: { 10: 1, 20: 1, 30: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockAddCartItem = jest.fn( ( item ) => {
			if ( item.id === 20 ) {
				return Promise.resolve(
					failure( 'Child 20 is out of stock.' )
				);
			}
			return Promise.resolve( success() );
		} );

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockEmitSyncEvent ).toHaveBeenCalledWith( {
			quantityChanges: { productsPendingAdd: [ 10, 20, 30 ] },
		} );
	} );

	it( 'fires none of the three once-per-batch effects when every child is rejected', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2 ],
			quantity: { 1: 1, 2: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockAddCartItem = jest.fn( ( item ) =>
			Promise.resolve( failure( `Child ${ item.id } is out of stock.` ) )
		);

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockTriggerAddedToCartEvent ).not.toHaveBeenCalled();
		expect( mockEmitSyncEvent ).not.toHaveBeenCalled();
		expect( mockSpeak ).not.toHaveBeenCalled();
	} );

	it( 'raises exactly one raw-text error notice per rejected child and none for accepted children, in a partial-success batch', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2, 3 ],
			quantity: { 1: 1, 2: 1, 3: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockAddCartItem = jest.fn( ( item ) => {
			if ( item.id === 1 ) {
				return Promise.resolve( failure( 'Child 1 is out of stock.' ) );
			}
			if ( item.id === 3 ) {
				return Promise.resolve( failure( 'Child 3 is out of stock.' ) );
			}
			return Promise.resolve( success() );
		} );

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockAddNotice ).toHaveBeenCalledTimes( 2 );
		expect( mockAddNotice ).toHaveBeenNthCalledWith( 1, {
			notice: 'Child 1 is out of stock.',
			type: 'error',
			dismissible: true,
		} );
		expect( mockAddNotice ).toHaveBeenNthCalledWith( 2, {
			notice: 'Child 3 is out of stock.',
			type: 'error',
			dismissible: true,
		} );
	} );

	it( 'raises one raw-text error notice per rejected child when every child is rejected', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1, 2 ],
			quantity: { 1: 1, 2: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockAddCartItem = jest.fn( ( item ) =>
			Promise.resolve( failure( `Child ${ item.id } is out of stock.` ) )
		);

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockAddNotice ).toHaveBeenCalledTimes( 2 );
		expect( mockAddNotice ).toHaveBeenNthCalledWith( 1, {
			notice: 'Child 1 is out of stock.',
			type: 'error',
			dismissible: true,
		} );
		expect( mockAddNotice ).toHaveBeenNthCalledWith( 2, {
			notice: 'Child 2 is out of stock.',
			type: 'error',
			dismissible: true,
		} );
	} );

	it( 'skips the announcement without error when the config lacks addedToCartText, while the other once-per-batch effects still fire', async () => {
		mockContext = makeContext( {
			groupedProductIds: [ 1 ],
			quantity: { 1: 1 },
		} );
		mockFindProduct = jest.fn( () => makeProduct() );
		mockGetConfig = jest.fn( () => undefined );

		const actions = loadBlockStore();
		await runAction( actions.batchAddToCart() );

		expect( mockSpeak ).not.toHaveBeenCalled();
		expect( mockTriggerAddedToCartEvent ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitSyncEvent ).toHaveBeenCalledTimes( 1 );
	} );
} );
