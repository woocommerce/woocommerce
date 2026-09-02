/**
 * External dependencies
 */
import type { AddCartItemOutcome } from '@woocommerce/stores/woocommerce/cart';
import type { RawShopperListItem } from '@woocommerce/stores/woocommerce/shopper-lists';

type BlockActions = {
	onClickAddToCart: () => Generator< unknown, void >;
};

// `frontend.ts` registers its block store under `woocommerce/wishlist` and opens
// the shared `woocommerce` cart store plus the `woocommerce/shopper-lists`
// store, all routed through the mocked `store()`.

// Single shared context the mocked `getContext` returns for the row under test.
let mockContext: {
	listItem?: RawShopperListItem;
	pendingKeys: Record< string, true >;
};

// Captured cart-store action spy; resolves an `AddCartItemOutcome` per test.
let mockAddCartItem: jest.Mock< Promise< AddCartItemOutcome > >;

// Captured shopper-lists `removeItem` spy and the block store's registered
// actions, populated when `frontend.ts` calls the mocked `store()`.
let mockRemoveItem: jest.Mock;
let mockBlockActions: BlockActions | null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		getContext: jest.fn( () => mockContext ),
		getElement: jest.fn( () => ( { ref: null } ) ),
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/wishlist' ) {
				mockBlockActions = definition?.actions ?? null;
				return {
					state: definition?.state,
					actions: definition?.actions,
				};
			}
			if ( name === 'woocommerce' ) {
				return { actions: { addCartItem: mockAddCartItem } };
			}
			// woocommerce/shopper-lists
			return {
				state: { lists: {} },
				actions: { removeItem: mockRemoveItem },
			};
		} ),
	} ),
	{ virtual: true }
);

// Side-effect store registrations `frontend.ts` imports for ordering only.
jest.mock( '@woocommerce/stores/woocommerce/shopper-lists', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/sanitize', () => ( { sanitizeHTML: jest.fn() } ), {
	virtual: true,
} );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives `*onClickAddToCart`.
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
 * Builds a wishlist list item with sensible purchasable defaults.
 *
 * @param overrides Partial fields overriding the defaults.
 * @return A list item suitable for the row context under test.
 */
function makeListItem(
	overrides: Partial< RawShopperListItem > = {}
): RawShopperListItem {
	return {
		key: 'list-key-1',
		id: 42,
		product_id: 42,
		variation_id: 0,
		quantity: 1,
		is_live: true,
		is_purchasable: true,
		name: 'Wishlist Product',
		permalink: null,
		images: [],
		variation: [],
		prices: null,
		price_html: '',
		image_html: '',
		date_added_gmt: '',
		...overrides,
	};
}

/**
 * Loads a fresh copy of the wishlist frontend module so it registers its block
 * store against the mocked `store()` and exposes its actions.
 *
 * @return The registered block-store actions.
 */
function loadBlockStore(): BlockActions {
	mockBlockActions = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockBlockActions ) {
		throw new Error( 'Wishlist store was not registered.' );
	}
	return mockBlockActions;
}

describe( 'Wishlist onClickAddToCart', () => {
	beforeEach( () => {
		mockContext = { pendingKeys: {} };
		mockRemoveItem = jest.fn( () => undefined );
		mockAddCartItem = jest.fn( () => Promise.resolve( { success: true } ) );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'removes the entry when addCartItem resolves a successful outcome', async () => {
		mockContext.listItem = makeListItem();

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'wishlist',
			'list-key-1'
		);
	} );

	it( 'removes the entry when the add succeeds for a product already in the cart as a meta line', async () => {
		// A meta line (e.g. a bundle child) resolves the add into a brand-new
		// standalone cart line rather than bumping the existing one; the
		// outcome the request itself settles with is what decides removal,
		// not the shape the cart ends up in.
		mockContext.listItem = makeListItem();
		mockAddCartItem = jest.fn( () => Promise.resolve( { success: true } ) );

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'wishlist',
			'list-key-1'
		);
	} );

	it( 'removes the entry when the add succeeds with a server-normalized quantity', async () => {
		// Wishlist always requests quantityToAdd: 1, but the server may still
		// resolve the line to a different final quantity (e.g. merging with
		// an existing line); that is still a success and must not block
		// removal.
		mockContext.listItem = makeListItem();
		mockAddCartItem = jest.fn( () => Promise.resolve( { success: true } ) );

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'wishlist',
			'list-key-1'
		);
	} );

	it( 'keeps the entry when addCartItem resolves a failed outcome', async () => {
		mockContext.listItem = makeListItem();
		mockAddCartItem = jest.fn( () =>
			Promise.resolve( {
				success: false,
				error: { message: 'Out of stock.' },
			} )
		);

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockRemoveItem ).not.toHaveBeenCalled();
	} );

	it( 'does not call addCartItem when the row is not purchasable', async () => {
		mockContext.listItem = makeListItem( { is_purchasable: false } );

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockAddCartItem ).not.toHaveBeenCalled();
		expect( mockRemoveItem ).not.toHaveBeenCalled();
	} );

	it( 'does not call addCartItem when the row is already pending', async () => {
		mockContext.listItem = makeListItem();
		mockContext.pendingKeys[ 'list-key-1' ] = true;

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockAddCartItem ).not.toHaveBeenCalled();
	} );

	it( 'clears the pending flag once the add settles, whether it succeeds or fails', async () => {
		mockContext.listItem = makeListItem();
		mockAddCartItem = jest.fn( () =>
			Promise.resolve( {
				success: false,
				error: { message: 'Out of stock.' },
			} )
		);

		const actions = loadBlockStore();
		await runAction( actions.onClickAddToCart() );

		expect( mockContext.pendingKeys[ 'list-key-1' ] ).toBeUndefined();
	} );
} );
