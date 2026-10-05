/**
 * Internal dependencies
 */
import type { RawShopperListItem, Store } from '../shopper-lists';

type MockStore = { state: Store[ 'state' ]; actions: Store[ 'actions' ] };
type WindowWithWp = Window & { wp?: { apiFetch?: jest.Mock } };

let mockRegisteredStore: MockStore | null = null;
const mockState = {} as Store[ 'state' ];

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: mockState,
				actions: definition?.actions,
			} as MockStore;
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

/**
 * Drives an Interactivity API async action generator to completion, feeding
 * rejections back into the generator like the real runtime does.
 *
 * @param action The async action return value cast to a generator.
 */
async function runAction( action: unknown ): Promise< void > {
	const iterator = action as Generator< unknown, unknown, unknown >;
	let next = iterator.next();
	while ( ! next.done ) {
		try {
			next = iterator.next( await next.value );
		} catch ( error ) {
			next = iterator.throw( error );
		}
	}
}

/**
 * Loads a fresh copy of the store and stubs `showNoticeError`, which would
 * otherwise import the store-notices module.
 */
function loadStore(): Store[ 'actions' ] {
	jest.isolateModules( () => require( '../shopper-lists' ) );
	const actions = mockRegisteredStore?.actions as Store[ 'actions' ];
	actions.showNoticeError = jest.fn();
	return actions;
}

const makeItem = ( key: string ): RawShopperListItem =>
	( { key, product_id: 12 } ) as RawShopperListItem;

const jsonResponse = (
	body: unknown,
	init: { status?: number; headers?: Record< string, string > } = {}
) =>
	new Response( JSON.stringify( body ), {
		...init,
		headers: { 'Content-Type': 'application/json', ...init.headers },
	} );

describe( 'woocommerce/shopper-lists store requests', () => {
	const originalFetch = global.fetch;
	let fetchMock: jest.Mock;

	beforeEach( () => {
		Object.assign( mockState, {
			restUrl: 'https://example.com/wp-json/',
			nonce: 'nonce-1',
			lists: {},
		} );
		fetchMock = jest.fn();
		global.fetch = fetchMock as unknown as typeof fetch;
	} );

	afterEach( () => {
		delete ( window as WindowWithWp ).wp;
		global.fetch = originalFetch;
	} );

	describe( 'when wp.apiFetch is loaded', () => {
		const useApiFetch = ( impl: () => Promise< Response > ) => {
			const apiFetch = jest.fn( impl );
			( window as WindowWithWp ).wp = { apiFetch };
			return apiFetch;
		};

		it( 'sends addItem through apiFetch so middleware can run', async () => {
			const item = makeItem( 'abc' );
			const apiFetch = useApiFetch( async () =>
				jsonResponse( item, {
					status: 201,
					headers: { Nonce: 'nonce-2' },
				} )
			);
			const actions = loadStore();

			await runAction(
				actions.addItem( 'wishlist', { product_id: 12 } )
			);

			expect( apiFetch ).toHaveBeenCalledWith( {
				method: 'POST',
				body: JSON.stringify( { product_id: 12 } ),
				path: 'wc/store/v1/shopper-lists/wishlist/items?_locale=site',
				headers: {
					'Content-Type': 'application/json',
					Nonce: 'nonce-1',
				},
				cache: 'no-store',
				parse: false,
			} );
			expect( fetchMock ).not.toHaveBeenCalled();
			expect( mockState.lists.wishlist.items ).toEqual( [ item ] );
			expect( mockState.nonce ).toBe( 'nonce-2' );
			expect( actions.showNoticeError ).not.toHaveBeenCalled();
		} );

		it( 'shows the server message when apiFetch rejects with a Response', async () => {
			useApiFetch( async () => {
				throw jsonResponse( { message: 'Nope' }, { status: 400 } );
			} );
			const actions = loadStore();

			await runAction(
				actions.addItem( 'wishlist', { product_id: 12 } )
			);

			expect( actions.showNoticeError ).toHaveBeenCalledWith(
				new Error( 'Nope' )
			);
			expect( mockState.lists.wishlist.items ).toEqual( [] );
		} );

		it( 'sends removeItem through apiFetch and drops the item', async () => {
			mockState.lists.wishlist = {
				items: [ makeItem( 'a/b' ), makeItem( 'c' ) ],
				isLoading: false,
			};
			const apiFetch = useApiFetch(
				async () => new Response( null, { status: 204 } )
			);
			const actions = loadStore();

			await runAction( actions.removeItem( 'wishlist', 'a/b' ) );

			expect( apiFetch ).toHaveBeenCalledWith(
				expect.objectContaining( {
					method: 'DELETE',
					path: 'wc/store/v1/shopper-lists/wishlist/items/a%2Fb?_locale=site',
				} )
			);
			expect(
				mockState.lists.wishlist.items.map( ( i ) => i.key )
			).toEqual( [ 'c' ] );
		} );
	} );

	describe( 'when wp.apiFetch is not loaded', () => {
		it( 'falls back to fetch with the seeded REST URL', async () => {
			const item = makeItem( 'abc' );
			fetchMock.mockResolvedValue(
				jsonResponse( item, { status: 201 } )
			);
			const actions = loadStore();

			await runAction(
				actions.addItem( 'wishlist', { product_id: 12 } )
			);

			expect( fetchMock ).toHaveBeenCalledWith(
				'https://example.com/wp-json/wc/store/v1/shopper-lists/wishlist/items',
				expect.objectContaining( {
					method: 'POST',
					cache: 'no-store',
					credentials: 'include',
				} )
			);
			expect( mockState.lists.wishlist.items ).toEqual( [ item ] );
		} );
	} );
} );
