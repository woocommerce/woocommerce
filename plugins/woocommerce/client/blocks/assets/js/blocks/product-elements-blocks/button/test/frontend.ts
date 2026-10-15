/**
 * The Product Button store's `addCartItem` action, with the `woocommerce` cart
 * store mocked at the module boundary the block opens through `store()`.
 */

type ButtonActions = {
	addCartItem: () => Generator< unknown, void >;
};

type ButtonContext = {
	quantityToAdd: number;
	displayViewCart: boolean;
};

// Single shared context the mocked `getContext` returns for the button.
let mockContext: ButtonContext;

// Captured cart-store action spy.
const mockAddCartItem = jest.fn();

// The product the mocked products store has in context.
const mockProductsState: {
	productInContext: { id: number; type: string } | null;
} = { productInContext: null };

// The block store's registered actions, populated when `frontend.ts` calls the
// mocked `store()`.
let mockButtonActions: ButtonActions | null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn( () => mockContext ),
		useLayoutEffect: jest.fn(),
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/product-button' ) {
				mockButtonActions = definition?.actions ?? null;
				return { state: {}, actions: definition?.actions };
			}
			if ( name === 'woocommerce' ) {
				return {
					state: { cart: { items: [] } },
					actions: { addCartItem: mockAddCartItem },
				};
			}
			if ( name === 'woocommerce/products' ) {
				return { state: mockProductsState };
			}
			return { state: {} };
		} ),
	} ),
	{ virtual: true }
);

// Side-effect store registrations `frontend.ts` imports for ordering only.
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives `*addCartItem`.
 *
 * @param action The async action return value, treated as a generator.
 * @return A promise resolving once the generator finishes.
 */
async function runAction( action: unknown ): Promise< void > {
	const iterator = action as Iterator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		const resolved = await next.value;
		next = iterator.next( resolved );
	}
}

/**
 * Loads a fresh copy of the Product Button frontend module so it registers its
 * block store against the mocked `store()` and exposes its actions.
 *
 * @return The registered block-store actions.
 */
function loadButtonActions(): ButtonActions {
	mockButtonActions = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockButtonActions ) {
		throw new Error( 'Product Button store was not registered.' );
	}
	return mockButtonActions;
}

describe( 'Product Button addCartItem action', () => {
	beforeEach( () => {
		mockContext = { quantityToAdd: 3, displayViewCart: false };
		mockProductsState.productInContext = { id: 42, type: 'simple' };
		mockAddCartItem.mockReturnValue( Promise.resolve( { success: true } ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'passes the cart store exactly { id, quantity } with the quantityToAdd of its context, and notices off', async () => {
		await runAction( loadButtonActions().addCartItem() );

		expect( mockAddCartItem.mock.calls ).toStrictEqual( [
			[ { id: 42, quantity: 3 }, { showCartUpdatesNotices: false } ],
		] );
		expect( mockContext.displayViewCart ).toBe( true );
	} );
} );
