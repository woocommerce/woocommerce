const STORAGE_KEY = 'storeApiNonce';

const getStoredNonce = () =>
	JSON.parse( window.localStorage.getItem( STORAGE_KEY ) );

/**
 * Loads the middleware in an isolated module registry so the module-level
 * nonce state (and the `apiFetch` instance it patches) is reset for every
 * test. The bootstrap values mirror the inline `wcBlocksMiddlewareConfig`
 * printed by `AssetsController`.
 *
 * @param {string} nonce     Page-embedded nonce.
 * @param {number} timestamp Page-embedded nonce timestamp.
 * @return {Function} The `apiFetch` instance patched by the middleware.
 */
const loadMiddleware = ( nonce, timestamp ) => {
	global.wcBlocksMiddlewareConfig = {
		storeApiNonce: nonce,
		storeApiNonceTimestamp: String( timestamp ),
	};

	let apiFetch;
	jest.isolateModules( () => {
		// eslint-disable-next-line @typescript-eslint/no-require-imports
		apiFetch = require( '@wordpress/api-fetch' ).default;
		// eslint-disable-next-line @typescript-eslint/no-require-imports
		require( '../store-api-nonce' );
	} );
	return apiFetch;
};

const storeNonce = ( nonce, timestamp ) =>
	window.localStorage.setItem(
		STORAGE_KEY,
		JSON.stringify( { nonce, timestamp } )
	);

describe( 'Store API nonce middleware', () => {
	beforeEach( () => {
		window.localStorage.clear();
	} );

	afterAll( () => {
		delete global.wcBlocksMiddlewareConfig;
	} );

	it( 'stores the page-embedded nonce when nothing is stored yet', () => {
		loadMiddleware( 'page-nonce', 100 );

		expect( getStoredNonce() ).toEqual( {
			nonce: 'page-nonce',
			timestamp: 100,
		} );
	} );

	it( 'keeps the stored nonce when the page-embedded nonce is older', () => {
		storeNonce( 'fresh-nonce', 200 );

		loadMiddleware( 'cached-page-nonce', 100 );

		expect( getStoredNonce() ).toEqual( {
			nonce: 'fresh-nonce',
			timestamp: 200,
		} );
	} );

	it( 'replaces the stored nonce when the page-embedded nonce is newer', () => {
		storeNonce( 'old-nonce', 100 );

		loadMiddleware( 'page-nonce', 200 );

		expect( getStoredNonce() ).toEqual( {
			nonce: 'page-nonce',
			timestamp: 200,
		} );
	} );

	it( 'trusts a nonce from response headers even when the stored timestamp is ahead', () => {
		storeNonce( 'skewed-nonce', 999999 );

		const apiFetch = loadMiddleware( 'page-nonce', 100 );
		apiFetch.setNonce(
			new Headers( {
				Nonce: 'server-nonce',
				'Nonce-Timestamp': '300',
			} )
		);

		expect( getStoredNonce() ).toEqual( {
			nonce: 'server-nonce',
			timestamp: 300,
		} );
	} );

	it( 'accepts nonces from a plain headers object', () => {
		const apiFetch = loadMiddleware( 'page-nonce', 100 );
		apiFetch.setNonce( {
			Nonce: 'server-nonce',
			'Nonce-Timestamp': '300',
		} );

		expect( getStoredNonce() ).toEqual( {
			nonce: 'server-nonce',
			timestamp: 300,
		} );
	} );

	it( 'sends the trusted server nonce on subsequent Store API requests', async () => {
		storeNonce( 'skewed-nonce', 999999 );

		const apiFetch = loadMiddleware( 'page-nonce', 100 );
		apiFetch.setNonce( {
			Nonce: 'server-nonce',
			'Nonce-Timestamp': '300',
		} );

		const fetchHandler = jest.fn( ( options ) => options );
		apiFetch.setFetchHandler( fetchHandler );
		await apiFetch( {
			path: '/wc/store/v1/cart/add-item',
			method: 'POST',
		} );

		expect( fetchHandler ).toHaveBeenCalledWith(
			expect.objectContaining( {
				headers: expect.objectContaining( { Nonce: 'server-nonce' } ),
			} )
		);
	} );
} );
