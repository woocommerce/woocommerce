const mockCart = { items: [] as { id: number; quantity: number }[] };
const mockMiniCart = { isOpen: false };
const mockWaitForIdle = jest.fn();
const mockPrefetch = jest.fn();
const mockNavigate = jest.fn();
const mockGetConfig = jest.fn();
let mockRefresh: () => Generator;

jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ) );
jest.mock( '@wordpress/interactivity', () => ( {
	getConfig: mockGetConfig,
	store: (
		namespace: string,
		descriptor: { callbacks: { refreshCartReference: () => Generator } }
	) => {
		if ( namespace === 'woocommerce' ) {
			return {
				state: { cart: mockCart },
				actions: { waitForIdle: mockWaitForIdle },
			};
		}
		if ( namespace === 'woocommerce/mini-cart' ) {
			return { state: mockMiniCart };
		}
		mockRefresh = descriptor.callbacks.refreshCartReference;
		return descriptor;
	},
} ) );
jest.mock( '@wordpress/interactivity-router', () => ( {
	actions: { prefetch: mockPrefetch, navigate: mockNavigate },
} ) );

const refresh = async () => {
	const generator = mockRefresh();
	let step = generator.next();
	while ( ! step.done ) {
		try {
			step = generator.next( await step.value );
		} catch ( error ) {
			step = generator.throw( error );
		}
	}
};

const deferred = () => {
	let resolve!: () => void;
	const promise = new Promise< void >( ( done ) => {
		resolve = done;
	} );
	return { promise, resolve };
};

describe( 'cart reference refresh', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.resetAllMocks();
		mockCart.items = [];
		mockMiniCart.isOpen = false;
		window.history.replaceState( {}, '', '/shop/?orderby=price#products' );
		mockNavigate.mockImplementation( async ( url: string ) => {
			window.history.replaceState( { router: true }, '', url );
		} );
		jest.requireActual( '../cart-reference-frontend' );
	} );

	it( 'records the initial cart without fetching and refreshes once after settled changes', async () => {
		await refresh();
		expect( mockPrefetch ).not.toHaveBeenCalled();
		mockCart.items = [ { id: 1, quantity: 1 } ];
		const idle = deferred();
		mockWaitForIdle.mockReturnValue( idle.promise );
		const first = refresh();
		const second = refresh();
		expect( mockPrefetch ).not.toHaveBeenCalled();
		idle.resolve();
		await Promise.all( [ first, second ] );
		expect( mockPrefetch ).toHaveBeenCalledTimes( 1 );
		expect( mockNavigate ).not.toHaveBeenCalled();
		mockMiniCart.isOpen = true;
		await refresh();
		expect( mockNavigate ).toHaveBeenCalledTimes( 1 );
		expect(
			window.location.pathname +
				window.location.search +
				window.location.hash
		).toBe( '/shop/?orderby=price#products' );
		expect( window.history.state ).toEqual( { router: true } );
		await refresh();
		expect( mockNavigate ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not replay a classic add-to-cart action when fetching markup', async () => {
		window.history.replaceState(
			{},
			'',
			'/shop/?add-to-cart=1&orderby=price'
		);
		await refresh();
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockMiniCart.isOpen = true;
		await refresh();
		const requestUrl = new URL( mockPrefetch.mock.calls[ 0 ][ 0 ] );
		expect( requestUrl.searchParams.has( 'add-to-cart' ) ).toBe( false );
		expect( requestUrl.searchParams.get( 'orderby' ) ).toBe( 'price' );
		expect( mockNavigate.mock.calls[ 0 ][ 0 ] ).toBe( requestUrl.href );
		expect( window.location.search ).toBe( '?add-to-cart=1&orderby=price' );
	} );

	it( 'does not refresh for a reordered cart', async () => {
		mockCart.items = [
			{ id: 1, quantity: 1 },
			{ id: 2, quantity: 1 },
		];
		await refresh();
		mockCart.items.reverse();
		await refresh();
		expect( mockPrefetch ).not.toHaveBeenCalled();
	} );

	it( 'does not refresh when quantities change or the same product gains another cart line', async () => {
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockMiniCart.isOpen = true;
		await refresh();
		mockCart.items = [ { id: 1, quantity: 2 } ];
		await refresh();
		mockCart.items = [
			{ id: 1, quantity: 1 },
			{ id: 1, quantity: 1 },
		];
		await refresh();
		expect( mockPrefetch ).not.toHaveBeenCalled();
		expect( mockNavigate ).not.toHaveBeenCalled();
	} );

	it( 'refreshes when a product is removed, including the last product', async () => {
		mockCart.items = [
			{ id: 1, quantity: 1 },
			{ id: 2, quantity: 1 },
		];
		mockMiniCart.isOpen = true;
		await refresh();
		mockCart.items = [ { id: 2, quantity: 1 } ];
		await refresh();
		expect( mockPrefetch ).toHaveBeenCalledTimes( 1 );
		expect( mockNavigate ).toHaveBeenCalledTimes( 1 );
		mockCart.items = [];
		await refresh();
		expect( mockPrefetch ).toHaveBeenCalledTimes( 2 );
		expect( mockNavigate ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'does not overwrite a newer page navigation', async () => {
		await refresh();
		mockMiniCart.isOpen = true;
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockNavigate.mockImplementation( async () => {
			window.history.replaceState(
				{ page: 'other' },
				'',
				'/other-page/'
			);
		} );
		await refresh();
		expect( window.location.pathname ).toBe( '/other-page/' );
		expect( window.history.state ).toEqual( { page: 'other' } );
	} );

	it( 'does not navigate to a prefetched page after the shopper leaves it', async () => {
		await refresh();
		mockCart.items = [ { id: 1, quantity: 1 } ];
		await refresh();
		window.history.replaceState( {}, '', '/other-page/' );
		mockMiniCart.isOpen = true;
		await refresh();
		expect( mockNavigate ).not.toHaveBeenCalled();
	} );

	it( 'retries a rejected refresh when the drawer reopens', async () => {
		await refresh();
		mockMiniCart.isOpen = true;
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockNavigate.mockRejectedValueOnce( new Error( 'Network error' ) );
		await refresh();
		await refresh();
		expect( mockNavigate ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'retries a rejected prefetch', async () => {
		await refresh();
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockPrefetch.mockRejectedValueOnce( new Error( 'Network error' ) );
		await refresh();
		await refresh();
		expect( mockPrefetch ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'retries a navigation that the router cancelled without rejecting', async () => {
		await refresh();
		mockMiniCart.isOpen = true;
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockNavigate.mockResolvedValueOnce( undefined );
		await refresh();
		await refresh();
		expect( mockNavigate ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'keeps the newest refresh when an older navigation finishes later', async () => {
		await refresh();
		mockMiniCart.isOpen = true;
		mockCart.items = [ { id: 1, quantity: 1 } ];
		const started = deferred();
		const cancelled = deferred();
		mockNavigate.mockImplementationOnce( () => {
			started.resolve();
			return cancelled.promise;
		} );
		const older = refresh();
		await started.promise;
		mockCart.items = [
			{ id: 1, quantity: 1 },
			{ id: 2, quantity: 1 },
		];
		await refresh();
		cancelled.resolve();
		await older;
		await refresh();
		expect( mockNavigate ).toHaveBeenCalledTimes( 2 );
		expect(
			window.location.pathname +
				window.location.search +
				window.location.hash
		).toBe( '/shop/?orderby=price#products' );
	} );

	it( 'does not trigger a full-page reload when client navigation is disabled', async () => {
		mockGetConfig.mockReturnValue( { clientNavigationDisabled: true } );
		await refresh();
		mockCart.items = [ { id: 1, quantity: 1 } ];
		mockMiniCart.isOpen = true;
		await refresh();
		expect( mockPrefetch ).not.toHaveBeenCalled();
		expect( mockNavigate ).not.toHaveBeenCalled();
	} );
} );
