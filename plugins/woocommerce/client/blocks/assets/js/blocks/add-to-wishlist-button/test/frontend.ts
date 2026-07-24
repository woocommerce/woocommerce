/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { RawShopperListItem } from '@woocommerce/stores/woocommerce/shopper-lists';

type BlockContext = {
	productId: number;
	isVariableType: boolean;
	isPending: boolean;
};

type ATCWOContext = {
	selectedAttributes: Array< { attribute: string; value: string } >;
};

type BlockStore = {
	state: {
		effectiveProductId: number;
		currentItem: RawShopperListItem | null;
		isInWishlist: boolean;
		isDisabled: boolean;
		currentLabel: string;
	};
	actions: {
		onClickToggle: () => Generator< unknown, void >;
	};
};

// `frontend.ts` registers its own store under
// `woocommerce/add-to-wishlist-button` and opens the unified `woocommerce`
// store (for product data) plus the `woocommerce/shopper-lists` store, all
// routed through the mocked `store()`.

// The block's own iAPI context.
let mockContext: BlockContext;

// The ATCWO ancestor's context, read only for a variable product.
let mockAddToCartContext: ATCWOContext | undefined;

// Every `getContext()` call's namespace argument, recorded to prove the
// block only reaches into `woocommerce/add-to-cart-with-options` for a
// variable product.
let mockGetContextCalls: Array< string | undefined >;

// Every namespace `store()` is called with, recorded to prove the block
// opens the unified `woocommerce` namespace rather than the retired
// `woocommerce/products` one.
let mockStoreCallNames: string[];

// The unified `woocommerce` store's state: `itemInContext.product` backs the
// effective-product resolution, `itemInContext.baseProduct` backs the
// taxonomy-mapping read `onClickToggle` performs for a variable product.
let mockWooState: {
	itemInContext: {
		product: ProductResponseItem | null;
		baseProduct: ProductResponseItem | null;
	};
};

// The `woocommerce/shopper-lists` store's state/action spies.
let mockLists: Record< string, { items: RawShopperListItem[] } >;
let mockAddItem: jest.Mock;
let mockRemoveItem: jest.Mock;

let mockBlockStore: BlockStore | null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn( () => ( {
			addLabel: 'Add to wishlist',
			savedLabel: 'Saved',
			selectOptionsLabel: 'Select options',
		} ) ),
		getContext: jest.fn( ( namespace?: string ) => {
			mockGetContextCalls.push( namespace );
			if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
				return mockAddToCartContext;
			}
			return mockContext;
		} ),
		store: jest.fn(
			( name: string, definition?: Record< string, unknown > ) => {
				mockStoreCallNames.push( name );
				if ( name === 'woocommerce' ) {
					return { state: mockWooState };
				}
				if ( name === 'woocommerce/shopper-lists' ) {
					return {
						state: { lists: mockLists },
						actions: {
							addItem: mockAddItem,
							removeItem: mockRemoveItem,
						},
					};
				}
				// woocommerce/add-to-wishlist-button
				mockBlockStore = {
					state: definition?.state,
					actions: definition?.actions,
				} as BlockStore;
				return mockBlockStore;
			}
		),
	} ),
	{ virtual: true }
);

// Side-effect-only imports `frontend.ts` makes for module ordering; the
// mocked `store()` above handles every registration directly, so the real
// implementations must never load.
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
		// eslint-disable-next-line no-await-in-loop
		const resolved = await next.value;
		next = iterator.next( resolved );
	}
}

/**
 * Loads a fresh copy of the add-to-wishlist-button frontend module so it
 * registers its block store against the mocked `store()`.
 *
 * @return The registered block store.
 */
function loadStore(): BlockStore {
	mockBlockStore = null;
	jest.isolateModules( () => require( '../frontend' ) );
	if ( ! mockBlockStore ) {
		throw new Error( 'Add to Wishlist Button store was not registered.' );
	}
	return mockBlockStore;
}

/**
 * Builds a wishlist list item.
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

describe( 'Add to Wishlist Button frontend store', () => {
	beforeEach( () => {
		mockContext = {
			productId: 42,
			isVariableType: false,
			isPending: false,
		};
		mockAddToCartContext = undefined;
		mockGetContextCalls = [];
		mockStoreCallNames = [];
		mockWooState = {
			itemInContext: { product: null, baseProduct: null },
		};
		mockLists = {};
		mockAddItem = jest.fn( () => Promise.resolve() );
		mockRemoveItem = jest.fn( () => Promise.resolve() );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'opens the unified woocommerce store, never the retired woocommerce/products namespace', () => {
		loadStore();

		expect( mockStoreCallNames ).toContain( 'woocommerce' );
		expect( mockStoreCallNames ).not.toContain( 'woocommerce/products' );
	} );

	describe( 'state.effectiveProductId', () => {
		it( 'resolves to 0 when no product is in context', () => {
			mockWooState.itemInContext.product = null;

			const { state } = loadStore();

			expect( state.effectiveProductId ).toBe( 0 );
		} );

		it( 'resolves to the product id for a simple product', () => {
			mockWooState.itemInContext.product = {
				id: 5,
				type: 'simple',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.effectiveProductId ).toBe( 5 );
		} );

		it( 'resolves to 0 for a variable product whose variation is not yet selected', () => {
			mockContext.isVariableType = true;
			mockWooState.itemInContext.product = {
				id: 5,
				type: 'variable',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.effectiveProductId ).toBe( 0 );
		} );

		it( 'resolves to the resolved variation id once one is selected', () => {
			mockContext.isVariableType = true;
			mockWooState.itemInContext.product = {
				id: 7,
				type: 'variation',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.effectiveProductId ).toBe( 7 );
		} );
	} );

	describe( 'state.currentItem', () => {
		it( 'is null when no product is in context', () => {
			mockWooState.itemInContext.product = null;

			const { state } = loadStore();

			expect( state.currentItem ).toBeNull();
		} );

		it( 'is null when the wishlist has not loaded a list yet', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = {};

			const { state } = loadStore();

			expect( state.currentItem ).toBeNull();
		} );

		it( 'finds a non-variable item by id alone', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			const item = makeListItem( { id: 42 } );
			mockLists = { wishlist: { items: [ item ] } };

			const { state } = loadStore();

			expect( state.currentItem ).toBe( item );
		} );

		it( 'disambiguates a variable product by the shopper-picked attributes', () => {
			mockContext.isVariableType = true;
			mockWooState.itemInContext.product = {
				id: 20,
				type: 'variation',
			} as ProductResponseItem;
			mockAddToCartContext = {
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			};
			const matching = makeListItem( {
				id: 20,
				key: 'matching',
				variation: [
					{
						raw_attribute: 'attribute_pa_color',
						attribute: 'Color',
						value: 'blue',
					},
				],
			} );
			const nonMatching = makeListItem( {
				id: 20,
				key: 'non-matching',
				variation: [
					{
						raw_attribute: 'attribute_pa_color',
						attribute: 'Color',
						value: 'red',
					},
				],
			} );
			mockLists = {
				wishlist: { items: [ nonMatching, matching ] },
			};

			const { state } = loadStore();

			expect( state.currentItem ).toBe( matching );
		} );
	} );

	describe( 'state.isInWishlist', () => {
		it( 'is true when the effective product is on the list', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = {
				wishlist: { items: [ makeListItem( { id: 42 } ) ] },
			};

			const { state } = loadStore();

			expect( state.isInWishlist ).toBe( true );
		} );

		it( 'is false when the effective product is not on the list', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = { wishlist: { items: [] } };

			const { state } = loadStore();

			expect( state.isInWishlist ).toBe( false );
		} );
	} );

	describe( 'state.isDisabled', () => {
		it( 'is true while a request is pending', () => {
			mockContext.isPending = true;
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.isDisabled ).toBe( true );
		} );

		it( 'is true when no product resolves', () => {
			mockWooState.itemInContext.product = null;

			const { state } = loadStore();

			expect( state.isDisabled ).toBe( true );
		} );

		it( 'is false once a product resolves and nothing is pending', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;

			const { state } = loadStore();

			expect( state.isDisabled ).toBe( false );
		} );
	} );

	describe( 'state.currentLabel', () => {
		it( 'shows the select-options label when nothing is yet selectable', () => {
			mockWooState.itemInContext.product = null;

			const { state } = loadStore();

			expect( state.currentLabel ).toBe( 'Select options' );
		} );

		it( 'shows the saved label once the effective product is on the list', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = {
				wishlist: { items: [ makeListItem( { id: 42 } ) ] },
			};

			const { state } = loadStore();

			expect( state.currentLabel ).toBe( 'Saved' );
		} );

		it( 'shows the add label when the effective product is not on the list', () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = { wishlist: { items: [] } };

			const { state } = loadStore();

			expect( state.currentLabel ).toBe( 'Add to wishlist' );
		} );
	} );

	describe( 'actions.onClickToggle', () => {
		it( 'does nothing while a request is already pending', async () => {
			mockContext.isPending = true;
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockRemoveItem ).not.toHaveBeenCalled();
		} );

		it( 'does nothing when no product resolves', async () => {
			mockWooState.itemInContext.product = null;

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).not.toHaveBeenCalled();
			expect( mockRemoveItem ).not.toHaveBeenCalled();
		} );

		it( 'removes the existing entry when the effective product is already on the list', async () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			const existing = makeListItem( { id: 42, key: 'existing-key' } );
			mockLists = { wishlist: { items: [ existing ] } };

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockRemoveItem ).toHaveBeenCalledWith(
				'wishlist',
				'existing-key'
			);
			expect( mockAddItem ).not.toHaveBeenCalled();
		} );

		it( 'adds a simple product with no variation payload', async () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = { wishlist: { items: [] } };

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).toHaveBeenCalledWith( 'wishlist', {
				product_id: 42,
			} );
		} );

		it( 'maps the selected attributes to taxonomy slugs via the in-context base product', async () => {
			mockContext.isVariableType = true;
			mockWooState.itemInContext.product = {
				id: 20,
				type: 'variation',
			} as ProductResponseItem;
			mockWooState.itemInContext.baseProduct = {
				id: 10,
				type: 'variable',
				attributes: [ { name: 'Color', taxonomy: 'pa_color' } ],
			} as unknown as ProductResponseItem;
			mockAddToCartContext = {
				selectedAttributes: [ { attribute: 'Color', value: 'blue' } ],
			};
			mockLists = { wishlist: { items: [] } };

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).toHaveBeenCalledWith( 'wishlist', {
				product_id: 20,
				variation: [ { attribute: 'pa_color', value: 'blue' } ],
			} );
		} );

		it( 'falls back to the raw attribute name for a non-taxonomy custom attribute', async () => {
			mockContext.isVariableType = true;
			mockWooState.itemInContext.product = {
				id: 20,
				type: 'variation',
			} as ProductResponseItem;
			mockWooState.itemInContext.baseProduct = {
				id: 10,
				type: 'variable',
				attributes: [ { name: 'Style', taxonomy: null } ],
			} as unknown as ProductResponseItem;
			mockAddToCartContext = {
				selectedAttributes: [ { attribute: 'Style', value: 'bold' } ],
			};
			mockLists = { wishlist: { items: [] } };

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockAddItem ).toHaveBeenCalledWith( 'wishlist', {
				product_id: 20,
				variation: [ { attribute: 'Style', value: 'bold' } ],
			} );
		} );

		it( 'clears the pending flag once the request settles', async () => {
			mockWooState.itemInContext.product = {
				id: 42,
				type: 'simple',
			} as ProductResponseItem;
			mockLists = { wishlist: { items: [] } };

			const { actions } = loadStore();
			await runAction( actions.onClickToggle() );

			expect( mockContext.isPending ).toBe( false );
		} );
	} );
} );
