/**
 * External dependencies
 */
import type {
	OptimisticCartItem,
	Store as WooCommerce,
} from '@woocommerce/stores/woocommerce/cart';
import type { RawShopperListItem } from '@woocommerce/stores/woocommerce/shopper-lists';

type CartItems = WooCommerce[ 'state' ][ 'cart' ][ 'items' ];
type BlockActions = {
	onClickMoveToCart: () => Generator< unknown, void >;
};

// `frontend.ts` registers its block store under `woocommerce/saved-for-later`
// and opens the shared `woocommerce` cart store plus the
// `woocommerce/shopper-lists` store, all routed through the mocked `store()`.

// Single shared context the mocked `getContext` returns for the row under test.
let mockContext: {
	listItem?: RawShopperListItem;
	pendingKeys: Record< string, true >;
};

// The cart store's mutable line list and selector, controlled per test.
let mockCartItems: CartItems;
let mockFindItemInCart: jest.Mock;

// Captured cart-store action spies.
let mockAddCartItem: jest.Mock;

// Captured shopper-lists `removeItem` spy and the block store's registered
// actions, populated when `frontend.ts` calls the mocked `store()`.
let mockRemoveItem: jest.Mock;
let mockBlockActions: BlockActions | null;

// Stands in for `doesCartItemMatchAttributes`; a test controls which seeded
// lines count as variation matches.
let mockAttributeMatcher: ( cartItem: OptimisticCartItem ) => boolean;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		getContext: jest.fn( () => mockContext ),
		getElement: jest.fn( () => ( { ref: null } ) ),
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/saved-for-later' ) {
				mockBlockActions = definition?.actions ?? null;
				return {
					state: definition?.state,
					actions: definition?.actions,
				};
			}
			if ( name === 'woocommerce' ) {
				return {
					state: {
						get cart() {
							return { items: mockCartItems };
						},
						findItemInCart: ( ...args: unknown[] ) =>
							mockFindItemInCart( ...args ),
					},
					actions: { addCartItem: mockAddCartItem },
				};
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

// Matching is delegated to the cart store's `doesCartItemMatchAttributes`
// semantics; the test controls which lines match via `mockAttributeMatcher`.
jest.mock(
	'../../../base/utils/variations/does-cart-item-match-attributes',
	() => ( {
		doesCartItemMatchAttributes: ( cartItem: OptimisticCartItem ) =>
			mockAttributeMatcher( cartItem ),
	} )
);

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives `*onClickMoveToCart`.
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
 * Builds a saved-for-later list item with sensible purchasable defaults.
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
		name: 'Saved Product',
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
 * Builds a minimal cart line for seeding `cart.items`.
 *
 * @param overrides Partial cart-line fields overriding the defaults.
 * @return A cart line.
 */
function makeCartLine(
	overrides: Partial< OptimisticCartItem > = {}
): OptimisticCartItem {
	return {
		id: 42,
		type: 'simple',
		quantity: 1,
		...overrides,
	} as OptimisticCartItem;
}

/**
 * Loads a fresh copy of the saved-for-later frontend module so it registers its
 * block store against the mocked `store()` and exposes its actions.
 *
 * @return The registered block-store actions.
 */
function loadBlockStore(): BlockActions {
	mockBlockActions = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockBlockActions ) {
		throw new Error( 'Saved-for-later store was not registered.' );
	}
	return mockBlockActions;
}

describe( 'Saved-for-later onClickMoveToCart success detection', () => {
	beforeEach( () => {
		mockContext = { pendingKeys: {} };
		mockCartItems = [];
		mockFindItemInCart = jest.fn();
		mockRemoveItem = jest.fn( () => undefined );
		mockAttributeMatcher = () => false;
		// By default the add resolves without mutating the cart; individual
		// tests install an `addCartItem` that mutates `mockCartItems` to model
		// the server-reconciled cart.
		mockAddCartItem = jest.fn( () => Promise.resolve() );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'removes the entry when a meta-only product gains a new standalone line (before-sum 1, after-sum 2)', async () => {
		// The product exists only as a single meta line at quantity 1; the add
		// resolves server-side as a separate standalone line, so the total
		// quantity across matching lines goes 1 -> 2.
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [ makeCartLine( { id: 42, quantity: 1 } ) ];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [
				makeCartLine( { id: 42, quantity: 1 } ),
				makeCartLine( { id: 42, quantity: 1, key: 'new-line' } ),
			];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'removes the entry when an existing standalone line is incremented (before-sum 1, after-sum 2)', async () => {
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [ makeCartLine( { id: 42, quantity: 1 } ) ];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [ makeCartLine( { id: 42, quantity: 2 } ) ];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'preserves the entry when the server rejects the add and the cart is unchanged (before-sum equals after-sum)', async () => {
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [ makeCartLine( { id: 42, quantity: 1 } ) ];
		// addCartItem swallows the error and leaves the cart untouched.
		mockAddCartItem = jest.fn( () => Promise.resolve() );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).not.toHaveBeenCalled();
	} );

	it( 'sums over all matching lines rather than a single id-matched line', async () => {
		// Two matching lines exist before (1 + 2 = 3). The add bumps one of them
		// so the total becomes 4. A single-line read of just one matched line
		// could miss the growth; summing all matching lines detects it.
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [
			makeCartLine( { id: 42, quantity: 1, key: 'a' } ),
			makeCartLine( { id: 42, quantity: 2, key: 'b' } ),
		];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [
				makeCartLine( { id: 42, quantity: 2, key: 'a' } ),
				makeCartLine( { id: 42, quantity: 2, key: 'b' } ),
			];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'compares total quantity, not the cart-line count', async () => {
		// The line count stays at 1 across the add (an in-place increment), so a
		// count-based check would read no change. The total quantity grows
		// 1 -> 3, so a quantity sum correctly detects success.
		mockContext.listItem = makeListItem( { id: 42, quantity: 2 } );
		mockCartItems = [ makeCartLine( { id: 42, quantity: 1 } ) ];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [ makeCartLine( { id: 42, quantity: 3 } ) ];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'is independent of cart-line ordering', async () => {
		// Same lines as the multi-line case but seeded in reverse order before
		// and after; the result must not depend on iteration order.
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [
			makeCartLine( { id: 99, quantity: 5, key: 'other' } ),
			makeCartLine( { id: 42, quantity: 1, key: 'a' } ),
		];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [
				makeCartLine( { id: 42, quantity: 1, key: 'new' } ),
				makeCartLine( { id: 99, quantity: 5, key: 'other' } ),
				makeCartLine( { id: 42, quantity: 1, key: 'a' } ),
			];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'ignores non-matching product lines when summing', async () => {
		// A different product (id 99) churns its quantity across the add while
		// the saved product (id 42) is unchanged. Summing only matching lines
		// must read no growth and preserve the entry.
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [
			makeCartLine( { id: 42, quantity: 1 } ),
			makeCartLine( { id: 99, quantity: 1, key: 'other' } ),
		];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [
				makeCartLine( { id: 42, quantity: 1 } ),
				makeCartLine( { id: 99, quantity: 8, key: 'other' } ),
			];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).not.toHaveBeenCalled();
	} );

	it( 'sums over lines matching id and variation attributes for a variation entry', async () => {
		mockContext.listItem = makeListItem( {
			id: 7,
			variation_id: 7,
			quantity: 1,
			variation: [
				{
					raw_attribute: 'attribute_pa_color',
					attribute: 'Color',
					value: 'blue',
				},
			],
		} );
		// Variation lines carry a same-length `variation` array (matching the
		// selector's length guard); the attribute matcher decides which one is
		// the saved product. One variation line of the saved product plus a
		// same-id line with a different (non-matching) variation.
		const variationAttr = [
			{
				raw_attribute: 'attribute_pa_color',
				attribute: 'Color',
				value: 'x',
			},
		];
		const matchingLine = makeCartLine( {
			id: 7,
			type: 'variation',
			quantity: 1,
			key: 'match',
			variation: variationAttr,
		} );
		const otherVariationLine = makeCartLine( {
			id: 7,
			type: 'variation',
			quantity: 4,
			key: 'other-variation',
			variation: variationAttr,
		} );
		mockCartItems = [ matchingLine, otherVariationLine ];
		// Only the matching variation line satisfies the attribute matcher.
		mockAttributeMatcher = ( cartItem ) => cartItem.key === 'match';
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [
				makeCartLine( {
					id: 7,
					type: 'variation',
					quantity: 2,
					key: 'match',
					variation: variationAttr,
				} ),
				makeCartLine( {
					id: 7,
					type: 'variation',
					quantity: 4,
					key: 'other-variation',
					variation: variationAttr,
				} ),
			];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockRemoveItem ).toHaveBeenCalledWith(
			'saved-for-later',
			'list-key-1'
		);
	} );

	it( 'does not call findItemInCart for the success check (consumer sums cart.items directly)', async () => {
		// The before/after read sums `cart.items` itself; `findItemInCart` must
		// not be the mechanism for the success comparison.
		mockContext.listItem = makeListItem( { id: 42, quantity: 1 } );
		mockCartItems = [ makeCartLine( { id: 42, quantity: 1 } ) ];
		mockAddCartItem = jest.fn( () => {
			mockCartItems = [ makeCartLine( { id: 42, quantity: 2 } ) ];
			return Promise.resolve();
		} );

		const actions = loadBlockStore();
		await runAction( actions.onClickMoveToCart() );

		expect( mockFindItemInCart ).not.toHaveBeenCalled();
		expect( mockRemoveItem ).toHaveBeenCalled();
	} );
} );
