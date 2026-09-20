/**
 * Store-level proof for the six proposal use cases no core block produces
 * (5 Sticky bar, 6 Bundle, 9 Gift message, 10 Product selector, 11 Navigation
 * sync, 12 Product switcher — proposal-68885.md). Each suite drives the real
 * `scope.ts` + `cart-actions.ts` modules exactly as `index.ts` binds them,
 * simulating the extension or page script the proposal shows for that
 * scenario, using only the members R1 lists. No source file is exercised
 * through a private helper or a block namespace.
 */

/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { ScopeState, ProductScopeContext } from '../scope';
import type { CatalogState } from '../catalog';
import type { CartActionsState, OptimisticCartItem } from '../cart-actions';

type MockState = CatalogState &
	ScopeState &
	CartActionsState & {
		restUrl: string;
		nonce: string;
	};

/**
 * The cart plane's raw, undriven generator-function actions, exactly as
 * `cart-actions.ts` exports them. See `test/cart.ts`'s `RawCartActions` for
 * why the raw generators (rather than `CartActionsActions`'s
 * Promise-returning shape) are what this suite drives, via {@link runAction}.
 */
type RawCartActions = typeof import('../cart-actions').cartActions;

let mockContext: ProductScopeContext | null = null;
let mockState: MockState;

/**
 * The `restUrl` / `nonce` every test's mock state seeds, mirroring what
 * `BlocksSharedState.php` seeds onto the real store.
 */
function baseState(): Pick< MockState, 'restUrl' | 'nonce' > {
	return {
		restUrl: 'https://example.com/wp-json/',
		nonce: 'test-nonce-123',
	};
}

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: jest.fn(),
		getContext: jest.fn( () => mockContext ),
		// `notices.ts` pulls in `does-cart-item-match-attributes.ts`, which
		// calls `store()` for its own read of `state.products` /
		// `state.productVariations`. Neither `scope.ts` nor `cart-actions.ts`
		// calls `store()` itself (see their `bindState` / `bindCartState`
		// docblocks), so this generic stand-in only ever backs that one
		// incidental call.
		store: jest.fn( () => ( {
			state: { products: {}, productVariations: {} },
			actions: {},
		} ) ),
	} ),
	{ virtual: true }
);

jest.mock( '../legacy-events', () => ( {
	triggerAddedToCartEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

// `showNoticeError` / `updateNotices` are mocked so a mutation's notice
// side effect never needs a real store-notices round trip; every other
// export (the pure diff/matching helpers `addCartItem` relies on) keeps its
// real implementation via `requireActual`.
jest.mock( '../notices', () => ( {
	...jest.requireActual( '../notices' ),
	showNoticeError: jest.fn(),
	updateNotices: jest.fn(),
} ) );

/**
 * A single mutation captured off the batch endpoint's request body.
 */
type CapturedRequest = {
	/** The Store API path the mutation targeted, e.g. `/wc/store/v1/cart/add-item`. */
	path: string;
	/** The HTTP method of the mutation. */
	method: string;
	/** The parsed JSON body posted for the mutation. */
	body: Record< string, unknown >;
};

/**
 * Drives an Interactivity API async action generator to completion. See
 * `test/cart.ts`'s `runAction` for the full rationale (yielded values are
 * awaited and fed back in; a rejection is routed through `iterator.throw()`
 * so the action's own `try`/`catch` runs).
 *
 * @param action The async action return value cast to a generator.
 * @return A promise resolving to the generator's final (`done`) return value.
 */
async function runAction( action: unknown ): Promise< unknown > {
	const iterator = action as Generator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		try {
			const resolved = await next.value;
			next = iterator.next( resolved );
		} catch ( error ) {
			next = iterator.throw( error );
		}
	}
	return next.value;
}

/**
 * Loads a fresh copy of the unified store's scope and cart planes, bound to
 * one shared mock state exactly as `index.ts` binds the real modules, and
 * resolves the nonce gate. Each call re-requires the modules in isolation so
 * a test starts from a clean mutation queue and a fresh module-level
 * nonce-ready promise.
 *
 * @return The freshly bound scope state and cart actions.
 */
async function loadStore(): Promise< {
	scopeState: ScopeState;
	actions: RawCartActions;
} > {
	let scopeState!: ScopeState;
	let actions!: RawCartActions;

	jest.isolateModules( () => {
		const catalogModule =
			require( '../catalog' ) as typeof import('../catalog');
		const scopeModule = require( '../scope' ) as typeof import('../scope');
		const cartActionsModule =
			require( '../cart-actions' ) as typeof import('../cart-actions');

		mockState = {
			...catalogModule.catalogState,
			...cartActionsModule.cartActionsState,
			...baseState(),
		} as MockState;
		Object.defineProperties(
			mockState,
			Object.getOwnPropertyDescriptors( scopeModule.scopeState )
		);
		scopeModule.bindState( mockState );

		scopeState = scopeModule.scopeState;
		actions = cartActionsModule.cartActions;
		cartActionsModule.bindCartState(
			mockState as unknown as Parameters<
				typeof cartActionsModule.bindCartState
			>[ 0 ],
			{ refreshCart: actions.refreshCart }
		);
	} );

	await runAction( actions.refreshCart() );
	return { scopeState, actions };
}

/**
 * Installs a `global.fetch` mock that records every mutation routed through
 * the batch endpoint and replies with canned successful responses, echoing
 * the optimistic cart back as server state so the mutation queue commits
 * (rather than rolls back) it. See `test/cart.ts`'s `mockBatchFetch` for the
 * full rationale.
 *
 * @return The array that accumulates captured mutation requests.
 */
function mockBatchFetch(): CapturedRequest[] {
	const captured: CapturedRequest[] = [];
	global.fetch = jest.fn(
		async ( _url: RequestInfo | URL, init?: RequestInit ) => {
			if ( ! init?.body ) {
				return new Response(
					JSON.stringify( { items: [], totals: {}, errors: [] } ),
					{ headers: { Nonce: 'test-nonce-123' } }
				);
			}
			const parsed = JSON.parse( init.body as string ) as {
				requests: CapturedRequest[];
			};
			parsed.requests.forEach( ( request ) => captured.push( request ) );
			const serverCart = JSON.parse( JSON.stringify( mockState.cart ) );
			const responses = parsed.requests.map( () => ( {
				status: 200,
				body: serverCart,
			} ) );
			return new Response( JSON.stringify( { responses } ), {
				headers: { Nonce: 'test-nonce-123' },
			} );
		}
	) as unknown as typeof fetch;
	return captured;
}

const mockProduct = ( overrides: Partial< ProductResponseItem > = {} ) =>
	( { id: 1, type: 'simple', ...overrides } ) as ProductResponseItem;

describe( 'woocommerce store — use cases no core block produces', () => {
	afterEach( () => {
		jest.clearAllMocks();
		mockContext = null;
	} );

	describe( '5. Sticky bar — two surfaces, one scopeName, the template product', () => {
		it( 'shares one record between the form and the bar, and both resolve the template product', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.template = { productId: 100, variation: [] };
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Small' },
						],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( {
				id: 501,
				name: 'Blue Small',
			} );

			const formContext: ProductScopeContext = {
				scopeName: 't-shirt-page',
			};
			const barContext: ProductScopeContext = {
				scopeName: 't-shirt-page',
			};

			// Neither surface names a product: both resolve the template's,
			// before either surface has written anything.
			mockContext = formContext;
			expect( scopeState.productScope.productId ).toBe( 100 );
			expect( scopeState.productScope.product ).toBe(
				mockState.products[ 100 ]
			);
			mockContext = barContext;
			expect( scopeState.productScope.productId ).toBe( 100 );
			expect( scopeState.productScope.product ).toBe(
				mockState.products[ 100 ]
			);

			// The form picks Blue / Small.
			mockContext = formContext;
			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Small' },
			];

			// The bar sets the quantity.
			mockContext = barContext;
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 3;

			expect(
				mockState.productScopes[ 't-shirt-page' ].draftCartItem
			).toEqual( {
				id: 100,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
					{ attribute: 'attribute_pa_size', value: 'Small' },
				],
				quantity: 3,
			} );

			// The change made on the bar is read back on the form, and vice
			// versa — the shared record, and both still resolve the matched
			// variation.
			mockContext = formContext;
			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 3 );
			expect( scopeState.productScope.productVariation ).toBe(
				mockState.productVariations[ 501 ]
			);
			mockContext = barContext;
			expect( scopeState.productScope.variation ).toEqual( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Small' },
			] );
			expect( scopeState.productScope.productVariation ).toBe(
				mockState.productVariations[ 501 ]
			);

			// Neither surface added to cart in this scenario.
			expect( mockState.cart.items ).toEqual( [] );
		} );
	} );

	describe( '6. Bundle — per-slot scopes composed into one payload', () => {
		it( 'posts the slots’ drafts under an extension prop and deletes the slot records afterwards', async () => {
			const captured = mockBatchFetch();
			const { scopeState, actions } = await loadStore();
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Medium' },
						],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );
			mockState.products[ 11 ] = mockProduct( { id: 11 } );

			// A scope untouched by the bundle, to prove deleting the slots'
			// records leaves other scopes alone.
			mockState.productScopes[ 'unrelated-scope' ] = {
				draftCartItem: { id: 999, variation: [], quantity: 1 },
			};

			// Slot 1: an ordinary product scope with its own productId and
			// scopeName, configured with a variation.
			mockContext = { productId: 100, scopeName: 'bundle:slot-1' };
			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			];

			// Slot 2: the simple child, configured with a quantity.
			mockContext = { productId: 11, scopeName: 'bundle:slot-2' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 2;

			const slot1Draft =
				mockState.productScopes[ 'bundle:slot-1' ].draftCartItem;
			const slot2Draft =
				mockState.productScopes[ 'bundle:slot-2' ].draftCartItem;

			// The extension builds one payload carrying both slots' drafts
			// under its own extension prop, and posts it directly — the
			// payload form of `addCartItem`, so no record is cleared by the
			// action itself.
			await runAction(
				actions.addCartItem( {
					id: 40,
					quantity: 1,
					'wc-bundle-demo/children': {
						'bundle:slot-1': slot1Draft,
						'bundle:slot-2': slot2Draft,
					},
				} )
			);

			// The extension deletes the slots' records itself.
			delete mockState.productScopes[ 'bundle:slot-1' ];
			delete mockState.productScopes[ 'bundle:slot-2' ];

			expect(
				mockState.productScopes[ 'bundle:slot-1' ]
			).toBeUndefined();
			expect(
				mockState.productScopes[ 'bundle:slot-2' ]
			).toBeUndefined();
			expect(
				mockState.productScopes[ 'unrelated-scope' ]
			).toBeDefined();

			expect( captured ).toHaveLength( 1 );
			expect( captured[ 0 ].path ).toBe( '/wc/store/v1/cart/add-item' );
			expect( captured[ 0 ].body ).toEqual( {
				id: 40,
				quantity: 1,
				'wc-bundle-demo/children': {
					'bundle:slot-1': slot1Draft,
					'bundle:slot-2': slot2Draft,
				},
			} );

			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ] ).toMatchObject( {
				id: 40,
				quantity: 1,
				'wc-bundle-demo/children': {
					'bundle:slot-1': slot1Draft,
					'bundle:slot-2': slot2Draft,
				},
			} );
		} );
	} );

	describe( '9. Gift message — an extension prop on the same draft', () => {
		it( 'carries the gift message to the posted payload without leaking it into the declared context', async () => {
			const captured = mockBatchFetch();
			const { scopeState, actions } = await loadStore();
			mockState.products[ 11 ] = mockProduct( { id: 11 } );

			const context: ProductScopeContext = {
				productId: 11,
				scopeName: 'gift-form',
			};
			mockContext = context;

			// The extension writes its own extension prop onto the same
			// draft the quantity input writes to.
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem![ 'wc-gift-demo/message' ] =
				'Happy birthday!';

			expect(
				mockState.productScopes[ 'gift-form' ].draftCartItem
			).toEqual( {
				id: 11,
				variation: [],
				'wc-gift-demo/message': 'Happy birthday!',
			} );
			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 1 );
			expect( 'wc-gift-demo/message' in context ).toBe( false );

			await runAction( actions.addCartItem() );

			expect( captured[ 0 ].body.id ).toBe( 11 );
			expect( captured[ 0 ].body.quantity ).toBe( 1 );
			expect( captured[ 0 ].body[ 'wc-gift-demo/message' ] ).toBe(
				'Happy birthday!'
			);

			// The no-payload form removes the scope's record on success.
			expect( mockState.productScopes[ 'gift-form' ] ).toBeUndefined();

			expect( mockState.cart.items ).toHaveLength( 1 );
			const [ line ] = mockState.cart.items as OptimisticCartItem[];
			expect( line.id ).toBe( 11 );
			expect( line.quantity ).toBe( 1 );
			expect(
				( line as unknown as Record< string, unknown > )[
					'wc-gift-demo/message'
				]
			).toBe( 'Happy birthday!' );
		} );
	} );

	describe( '10. Product selector — re-pointing productId keeps the record', () => {
		it( 'keeps the one record, quantity included, and the product members follow the new id', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.products[ 11 ] = mockProduct( { id: 11 } );
			mockState.products[ 13 ] = mockProduct( { id: 13 } );
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Medium' },
						],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );

			mockContext = { productId: 11, scopeName: 'product-picker' };

			// Pick the T-Shirt, Blue / Medium, quantity 3.
			scopeState.productScope.productId = 100;
			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			];
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 3;
			expect( scopeState.productScope.product ).toBe(
				mockState.productVariations[ 501 ]
			);

			// Switch to the Belt: the variation is dropped, the quantity
			// stays, and the record is updated in place rather than a
			// second one being created.
			scopeState.productScope.productId = 13;
			scopeState.productScope.variation = [];

			expect(
				mockState.productScopes[ 'product-picker' ].draftCartItem
			).toEqual( { id: 13, variation: [], quantity: 3 } );
			expect( Object.keys( mockState.productScopes ) ).toEqual( [
				'product-picker',
			] );
			expect( scopeState.productScope.product ).toBe(
				mockState.products[ 13 ]
			);

			// Navigate away and back: a fresh page render declares the same
			// scope with its original (stale) productId, but the record
			// still wins, so the select still says Belt.
			mockContext = { productId: 11, scopeName: 'product-picker' };
			expect( scopeState.productScope.productId ).toBe( 13 );

			expect( mockState.cart.items ).toEqual( [] );
		} );
	} );

	describe( '11. Navigation sync — one scopeName declared on two pages', () => {
		it( 'reads the quantity typed on the first page from the second page’s markup', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.products[ 13 ] = mockProduct( { id: 13 } );

			const firstPage: ProductScopeContext = {
				productId: 13,
				scopeName: 'shared-form',
			};
			mockContext = firstPage;
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 4;

			// A distinct context object stands in for the second page's own
			// markup, declaring the same scopeName.
			const secondPage: ProductScopeContext = {
				productId: 13,
				scopeName: 'shared-form',
			};
			mockContext = secondPage;

			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 4 );
			expect(
				mockState.productScopes[ 'shared-form' ].draftCartItem
			).toEqual( { id: 13, variation: [], quantity: 4 } );

			expect( mockState.cart.items ).toEqual( [] );
		} );
	} );

	describe( '12. Product switcher — scopeName moves together with productId', () => {
		it( 'keeps each product’s own draft, and adding to cart clears only the current one', async () => {
			const captured = mockBatchFetch();
			const { scopeState, actions } = await loadStore();
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [
							{ name: 'Color', value: 'Green' },
							{ name: 'Size', value: 'Medium' },
						],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );
			mockState.products[ 200 ] = mockProduct( {
				id: 200,
				type: 'variable',
				variations: [
					{
						id: 701,
						attributes: [ { name: 'Size', value: 'Large' } ],
					},
				],
			} );
			mockState.productVariations[ 701 ] = mockProduct( { id: 701 } );

			const scopeNamesByProduct: Record< number, string > = {
				100: 'switcher:100',
				200: 'switcher:200',
			};
			const context: ProductScopeContext = {
				productId: 100,
				scopeName: 'switcher:100',
			};
			mockContext = context;

			// Configure the T-Shirt.
			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_color', value: 'Green' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			];
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 2;

			// The handler writes the context directly — not the envelope —
			// to switch which record the form reads, rather than re-pointing
			// the current one.
			context.productId = 200;
			delete context.variation;
			context.scopeName = scopeNamesByProduct[ 200 ];

			// Configure the Hoodie.
			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_size', value: 'Large' },
			];
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 3;

			expect(
				mockState.productScopes[ 'switcher:100' ].draftCartItem
			).toEqual( {
				id: 100,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Green' },
					{ attribute: 'attribute_pa_size', value: 'Medium' },
				],
				quantity: 2,
			} );
			expect(
				mockState.productScopes[ 'switcher:200' ].draftCartItem
			).toEqual( {
				id: 200,
				variation: [
					{ attribute: 'attribute_pa_size', value: 'Large' },
				],
				quantity: 3,
			} );

			// Switching back shows the record the new scope name addresses.
			context.productId = 100;
			delete context.variation;
			context.scopeName = scopeNamesByProduct[ 100 ];

			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 2 );
			expect( scopeState.productScope.productId ).toBe( 100 );
			expect( scopeState.productScope.variation ).toEqual( [
				{ attribute: 'attribute_pa_color', value: 'Green' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			] );

			// Adding to cart posts the product on screen and clears only its
			// own record.
			await runAction( actions.addCartItem() );

			expect( mockState.productScopes[ 'switcher:100' ] ).toBeUndefined();
			expect( mockState.productScopes[ 'switcher:200' ] ).toBeDefined();

			expect( captured[ 0 ].body ).toMatchObject( {
				id: 100,
				quantity: 2,
			} );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ] ).toMatchObject( {
				id: 100,
				quantity: 2,
			} );
		} );
	} );
} );
