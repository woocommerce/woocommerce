/**
 * External dependencies
 */
import type { RawShopperListItem } from '@woocommerce/stores/woocommerce/shopper-lists';

/**
 * A trimmed stand-in for `ProductResponseItem`: only the fields
 * `frontend.ts` itself reads (`id` and, for the base product, `attributes`).
 */
type FakeProduct = {
	id: number;
	attributes: Array< { name: string; taxonomy: string } >;
};

type BlockState = {
	effectiveProductId: number;
	currentItem: RawShopperListItem | null;
	isInWishlist: boolean;
	isDisabled: boolean;
	currentLabel: string;
};

type BlockActions = {
	onClickToggle: () => Generator< unknown, void >;
};

type ButtonConfig = {
	addLabel: string;
	savedLabel: string;
	selectOptionsLabel: string;
};

const CONFIG: ButtonConfig = {
	addLabel: 'Add to wishlist',
	savedLabel: 'Saved to wishlist',
	selectOptionsLabel: 'Select options first',
};

// `frontend.ts` registers its block store under
// `woocommerce/add-to-wishlist-button` and reads the shared `woocommerce`
// product scope plus the `woocommerce/shopper-lists` store, all routed
// through the mocked `store()`.

// The block's own iAPI context for the row under test.
let mockContext: {
	productId: number;
	isVariableType: boolean;
	isPending: boolean;
};

// The shared product scope `frontend.ts` reads instead of reaching into
// ATCWO's own namespace.
let mockProductScope: {
	baseProduct: FakeProduct | null;
	productVariation: FakeProduct | null;
	variation: Array< { attribute: string; value: string } >;
};

// The wishlist's current items, keyed like `shopperListsState.lists`.
let mockListItems: RawShopperListItem[];

let mockAddItem: jest.Mock;
let mockRemoveItem: jest.Mock;
let mockBlockState: BlockState | null;
let mockBlockActions: BlockActions | null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => CONFIG ),
		getContext: jest.fn( () => mockContext ),
		store: jest.fn( ( name: string, definition ) => {
			if ( name === 'woocommerce/add-to-wishlist-button' ) {
				mockBlockState = definition?.state ?? null;
				mockBlockActions = definition?.actions ?? null;
				return {
					state: definition?.state,
					actions: definition?.actions,
				};
			}
			if ( name === 'woocommerce' ) {
				return { state: { productScope: mockProductScope } };
			}
			// woocommerce/shopper-lists
			return {
				state: { lists: { wishlist: { items: mockListItems } } },
				actions: {
					addItem: mockAddItem,
					removeItem: mockRemoveItem,
				},
			};
		} ),
	} ),
	{ virtual: true }
);

// Side-effect store registrations `frontend.ts` imports for ordering only.
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ), {
	virtual: true,
} );
jest.mock( '@woocommerce/stores/woocommerce/shopper-lists', () => ( {} ), {
	virtual: true,
} );

/**
 * Drives an Interactivity API async action generator to completion.
 *
 * Each yielded value is awaited and fed back into the generator until done,
 * mirroring how the iAPI runtime drives `*onClickToggle`.
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
 * Builds a wishlist list item with sensible purchasable defaults.
 *
 * @param overrides Partial fields overriding the defaults.
 * @return A list item suitable for the row under test.
 */
function makeListItem(
	overrides: Partial< RawShopperListItem > = {}
): RawShopperListItem {
	return {
		key: 'list-key-1',
		id: 55,
		product_id: 42,
		variation_id: 55,
		quantity: 1,
		is_live: true,
		is_purchasable: true,
		name: 'Wishlist Product',
		permalink: null,
		images: [],
		variation: [
			{ raw_attribute: 'pa_color', attribute: 'Color', value: 'Red' },
		],
		prices: null,
		price_html: '',
		image_html: '',
		date_added_gmt: '',
		...overrides,
	};
}

/**
 * Loads a fresh copy of the add-to-wishlist-button frontend module so it
 * registers its block store against the mocked `store()` and exposes its
 * state and actions.
 *
 * @return The registered block-store state and actions.
 */
function loadBlockStore(): { state: BlockState; actions: BlockActions } {
	mockBlockState = null;
	mockBlockActions = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockBlockState || ! mockBlockActions ) {
		throw new Error( 'Add to Wishlist Button store was not registered.' );
	}
	return { state: mockBlockState, actions: mockBlockActions };
}

describe( 'Add to Wishlist Button', () => {
	beforeEach( () => {
		mockContext = {
			productId: 42,
			isVariableType: true,
			isPending: false,
		};
		mockProductScope = {
			baseProduct: {
				id: 42,
				attributes: [ { name: 'Color', taxonomy: 'pa_color' } ],
			},
			productVariation: null,
			variation: [],
		};
		mockListItems = [];
		mockAddItem = jest.fn( () => Promise.resolve() );
		mockRemoveItem = jest.fn( () => Promise.resolve() );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'for a simple product', () => {
		beforeEach( () => {
			mockContext.isVariableType = false;
		} );

		it( 'resolves the effective product id from the base product', () => {
			const { state } = loadBlockStore();

			expect( state.effectiveProductId ).toBe( 42 );
			expect( state.isDisabled ).toBe( false );
		} );
	} );

	describe( 'with no resolved variation', () => {
		it( 'is disabled and shows "Select options first"', () => {
			const { state } = loadBlockStore();

			expect( state.effectiveProductId ).toBe( 0 );
			expect( state.isDisabled ).toBe( true );
			expect( state.currentLabel ).toBe( CONFIG.selectOptionsLabel );
		} );

		it( 'does nothing when toggled', async () => {
			const { actions } = loadBlockStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockRemoveItem ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'with a resolved variation', () => {
		beforeEach( () => {
			mockProductScope.productVariation = { id: 55, attributes: [] };
			mockProductScope.variation = [
				{ attribute: 'Color', value: 'red' },
			];
		} );

		it( 'is enabled and shows the pressed star when the variation is already saved', () => {
			mockListItems = [ makeListItem() ];

			const { state } = loadBlockStore();

			expect( state.effectiveProductId ).toBe( 55 );
			expect( state.isDisabled ).toBe( false );
			expect( state.isInWishlist ).toBe( true );
			expect( state.currentLabel ).toBe( CONFIG.savedLabel );
		} );

		it( 'shows the empty star when the variation is not yet saved', () => {
			const { state } = loadBlockStore();

			expect( state.isInWishlist ).toBe( false );
			expect( state.currentLabel ).toBe( CONFIG.addLabel );
		} );

		it( 'saves the variation, mapping the attribute label to its taxonomy slug', async () => {
			const { actions } = loadBlockStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).toHaveBeenCalledWith( 'wishlist', {
				product_id: 55,
				variation: [ { attribute: 'pa_color', value: 'red' } ],
			} );
		} );

		it( 'removes the existing entry when the variation is already saved', async () => {
			mockListItems = [ makeListItem() ];

			const { actions } = loadBlockStore();
			await runAction( actions.onClickToggle() );

			expect( mockRemoveItem ).toHaveBeenCalledWith(
				'wishlist',
				'list-key-1'
			);
			expect( mockAddItem ).not.toHaveBeenCalled();
		} );

		it( 'does nothing when already pending', async () => {
			mockContext.isPending = true;

			const { actions } = loadBlockStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockRemoveItem ).not.toHaveBeenCalled();
		} );
	} );
} );
