/**
 * Store-level proof for the proposal's three draft lifecycle primitives
 * (proposal-68885.md, "Draft lifecycle"): removing a scope's draft, resetting
 * it to what the server seeded, and adding while keeping the draft — the
 * payload form of `addCartItem`. Everything else in the proposal's eight
 * lifecycle cases composes from these three.
 *
 * Each primitive is addressed at the reading element's declared scope name,
 * read via `getContext( 'woocommerce' ).scopeName`, defaulting to `_default`
 * exactly as `scope.ts` itself resolves a record's name — `state.productScope`
 * has no `scopeName` member of its own to read this from; `scopeName` is a
 * declared context prop (R1), not an envelope member.
 *
 * The "server seed" a reset restores is modelled as a value the test itself
 * holds, standing in for `getServerState( 'woocommerce' )` — outside R1's
 * member list — the same way PHP's `wp_interactivity_state()` seed is a
 * value fixed before the client ever runs.
 */

/**
 * External dependencies
 */
import { getContext } from '@wordpress/interactivity';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	ScopeState,
	ProductScopeContext,
	ProductScopeRecord,
} from '../scope';
import type { CatalogState } from '../catalog';
import type { AddCartItemPayload, CartActionsState } from '../cart-actions';

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
		// `cart-actions.ts` calls this around `showNoticeError`; the caller's
		// scope isn't this suite's concern (`test/notices.ts` covers it), so
		// this stand-in just runs the wrapped function as given.
		withScope: jest.fn( ( fn ) => fn ),
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
// real implementation via `requireActual`. `updateNotices`'s mock
// implementation is itself a generator function: the call sites delegate
// into it with `yield*`, which requires an iterable.
jest.mock( '../notices', () => ( {
	...jest.requireActual( '../notices' ),
	showNoticeError: jest.fn(),
	updateNotices: jest.fn( function* () {} ),
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

/**
 * Resolves the scope name the three lifecycle primitives address: the
 * reading element's declared `scopeName`, defaulting to `_default` — the
 * same resolution `scope.ts` itself applies internally, expressed here
 * through the one context prop R1 lists rather than a private helper.
 *
 * @return The scope name to address.
 */
function currentScopeName(): string {
	return (
		getContext< ProductScopeContext >( 'woocommerce' )?.scopeName ??
		'_default'
	);
}

describe( 'woocommerce store — draft lifecycle primitives', () => {
	afterEach( () => {
		jest.clearAllMocks();
		mockContext = null;
	} );

	describe( 'remove the draft', () => {
		it( 'deletes the addressed scope’s record, leaving other scopes untouched', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.products[ 55 ] = mockProduct( { id: 55 } );
			mockState.products[ 1 ] = mockProduct( { id: 1 } );

			mockContext = { productId: 55, scopeName: 'remove-me' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 3;

			mockContext = { productId: 1, scopeName: 'other' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 1;

			mockContext = { productId: 55, scopeName: 'remove-me' };
			delete mockState.productScopes[ currentScopeName() ];

			expect( mockState.productScopes[ 'remove-me' ] ).toBeUndefined();
			expect( mockState.productScopes.other.draftCartItem ).toEqual( {
				id: 1,
				variation: [],
				quantity: 1,
			} );
			expect( mockState.cart.items ).toEqual( [] );
		} );
	} );

	describe( 'reset to the server seed', () => {
		it( 'restores exactly what the server seeded', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.products[ 55 ] = mockProduct( { id: 55 } );

			const serverSeed: ProductScopeRecord = {
				draftCartItem: { id: 55, variation: [], quantity: 2 },
			};
			// What PHP's `wp_interactivity_state()` fixed before the client
			// ran — captured once, independent of the record the shopper is
			// about to edit.
			mockState.productScopes[ 'reset-me' ] = JSON.parse(
				JSON.stringify( serverSeed )
			);

			mockContext = { productId: 55, scopeName: 'reset-me' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 9;
			expect(
				mockState.productScopes[ 'reset-me' ].draftCartItem
			).toEqual( { id: 55, variation: [], quantity: 9 } );

			mockState.productScopes[ currentScopeName() ] = JSON.parse(
				JSON.stringify( serverSeed )
			);

			expect(
				mockState.productScopes[ 'reset-me' ].draftCartItem
			).toEqual( { id: 55, variation: [], quantity: 2 } );
			expect( mockState.cart.items ).toEqual( [] );
		} );

		it( 'deletes the record when the server seeded nothing', async () => {
			mockBatchFetch();
			const { scopeState } = await loadStore();
			mockState.products[ 77 ] = mockProduct( { id: 77 } );

			mockContext = { productId: 77, scopeName: 'reset-empty' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 5;
			expect( mockState.productScopes[ 'reset-empty' ] ).toBeDefined();

			const serverSeed: ProductScopeRecord | undefined = undefined;
			const scopeName = currentScopeName();
			if ( serverSeed === undefined ) {
				delete mockState.productScopes[ scopeName ];
			} else {
				mockState.productScopes[ scopeName ] = serverSeed;
			}

			expect( mockState.productScopes[ 'reset-empty' ] ).toBeUndefined();
			expect( mockState.cart.items ).toEqual( [] );
		} );
	} );

	describe( 'add, keep the draft', () => {
		it( 'posts the record’s own draft and leaves the record in place', async () => {
			const captured = mockBatchFetch();
			const { scopeState, actions } = await loadStore();
			mockState.products[ 88 ] = mockProduct( { id: 88 } );

			mockContext = { productId: 88, scopeName: 'keep-me' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 4;

			// The scope's own record always carries an `id` once created
			// (`ensureRecord` snapshots it), even though `DraftCartItemRecord`
			// types it as optional for a record that was never written.
			const draft = mockState.productScopes[ currentScopeName() ]
				.draftCartItem as AddCartItemPayload;

			// The payload form of `addCartItem` — passing the scope's own
			// draft explicitly — removes no record on success, unlike the
			// no-payload form.
			await runAction( actions.addCartItem( draft ) );

			expect( mockState.productScopes[ 'keep-me' ] ).toBeDefined();
			expect(
				mockState.productScopes[ 'keep-me' ].draftCartItem
			).toEqual( { id: 88, variation: [], quantity: 4 } );

			expect( captured[ 0 ].body ).toEqual( {
				id: 88,
				variation: [],
				quantity: 4,
			} );
			expect( mockState.cart.items ).toHaveLength( 1 );
			expect( mockState.cart.items[ 0 ] ).toMatchObject( {
				id: 88,
				quantity: 4,
			} );
		} );
	} );
} );
