/**
 * External dependencies
 */
import type { APIFetchOptions } from '@wordpress/api-fetch';

jest.mock( '@wordpress/api-fetch', () => {
	const triggerFetch = jest.fn();
	Object.assign( triggerFetch, {
		setNonce: jest.fn(),
		setCartHash: jest.fn(),
	} );
	return { __esModule: true, default: triggerFetch };
} );

type PendingFetch = {
	options: APIFetchOptions;
	resolve: ( value: unknown ) => void;
	reject: ( reason: unknown ) => void;
};

type BatchRequest = { path: string; method: string; body: unknown };

let fetches: PendingFetch[];
let apiFetchWithHeaders: ( options: APIFetchOptions ) => Promise< unknown >;

const settle = async () => {
	for ( let i = 0; i < 20; i++ ) {
		await Promise.resolve();
	}
};

/**
 * Runs the batching timers, then lets the promises settle.
 */
const tick = async ( ms = 0 ) => {
	jest.advanceTimersByTime( ms );
	await settle();
};

const batchRequests = ( index: number ) =>
	(
		fetches[ index ].options.data as {
			requests: BatchRequest[];
		}
	 ).requests.map( ( request ) => ( {
		path: request.path,
		body: request.body,
	} ) );

const batchItem = ( body: unknown, status = 200 ) => ( {
	status,
	body,
	headers: {},
} );

const respondToBatch = async ( index: number, items: unknown[] ) => {
	fetches[ index ].resolve( { responses: items } );
	await settle();
};

const respondToSingle = async ( index: number, body: unknown ) => {
	// A Response without a body stream, so the test doesn't depend on how the environment reads streams.
	const response = Object.setPrototypeOf(
		{
			json: () => Promise.resolve( body ),
			headers: new Headers(),
		},
		Response.prototype
	);
	fetches[ index ].resolve( response );
	await settle();
};

const updateItem = ( key: string, quantity: number, signal?: AbortSignal ) =>
	apiFetchWithHeaders( {
		path: '/wc/store/v1/cart/update-item',
		method: 'POST',
		data: { key, quantity },
		...( signal && { signal } ),
	} );

const selectShippingRate = ( rateId: string ) =>
	apiFetchWithHeaders( {
		path: '/wc/store/v1/cart/select-shipping-rate',
		method: 'POST',
		data: { package_id: 0, rate_id: rateId },
	} );

const addItem = ( id: number ) =>
	apiFetchWithHeaders( {
		path: '/wc/store/v1/cart/add-item',
		method: 'POST',
		data: { id, quantity: 1 },
	} );

const updateDraftOrder = ( paymentMethod: string ) =>
	apiFetchWithHeaders( {
		path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
		method: 'PUT',
		data: { payment_method: paymentMethod },
	} );

const UPDATE_ITEM_PATH = '/wc/store/v1/cart/update-item';
const BATCH_PATH = '/wc/store/v1/batch';

describe( 'apiFetchWithHeaders writes', () => {
	beforeEach( async () => {
		jest.useFakeTimers();
		// The queue lives in the module, so each test starts from a fresh copy.
		jest.resetModules();
		fetches = [];
		const { default: triggerFetch } =
			await import( '@wordpress/api-fetch' );
		jest.mocked( triggerFetch ).mockImplementation(
			( options: APIFetchOptions ) =>
				new Promise( ( resolve, reject ) => {
					fetches.push( { options, resolve, reject } );
				} )
		);
		( { apiFetchWithHeaders } = await import( '../shared-controls' ) );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'sends a write only after the server has finished the one before it', async () => {
		const first = updateItem( 'item-1', 2 );
		await tick();
		const second = updateItem( 'item-2', 3 );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );
		expect( fetches[ 0 ].options ).toMatchObject( {
			path: UPDATE_ITEM_PATH,
			data: { key: 'item-1', quantity: 2 },
			parse: false,
		} );

		await respondToSingle( 0, { items: 'first' } );
		await expect( first ).resolves.toMatchObject( {
			response: { items: 'first' },
		} );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options ).toMatchObject( {
			path: UPDATE_ITEM_PATH,
			data: { key: 'item-2', quantity: 3 },
		} );

		await respondToSingle( 1, { items: 'second' } );
		await expect( second ).resolves.toMatchObject( {
			response: { items: 'second' },
		} );
	} );

	it( 'sends update-item on its own and without waiting for the batching window when nothing is running', async () => {
		void updateItem( 'item-1', 2 );
		await tick();

		expect( fetches ).toHaveLength( 1 );
		expect( fetches[ 0 ].options.path ).toBe( UPDATE_ITEM_PATH );
	} );

	it( 'never puts update-item writes in a batch', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		void updateItem( 'item-1', 2 );
		void updateItem( 'item-2', 3 );
		void updateItem( 'item-3', 4 );
		await respondToBatch( 0, [ batchItem( {} ) ] );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options.path ).toBe( UPDATE_ITEM_PATH );

		await respondToSingle( 1, {} );
		expect( fetches ).toHaveLength( 3 );
		expect( fetches[ 2 ].options.path ).toBe( UPDATE_ITEM_PATH );

		await respondToSingle( 2, {} );
		expect( fetches ).toHaveLength( 4 );
		expect( fetches[ 3 ].options.path ).toBe( UPDATE_ITEM_PATH );
	} );

	it( 'sends the writes made while one is running together in one batch', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const second = addItem( 1 );
		const third = selectShippingRate( 'free_shipping:2' );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );

		await respondToBatch( 0, [ batchItem( {} ) ] );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options.path ).toBe( BATCH_PATH );
		expect( batchRequests( 1 ) ).toEqual( [
			{
				path: '/wc/store/v1/cart/add-item',
				body: { id: 1, quantity: 1 },
			},
			{
				path: '/wc/store/v1/cart/select-shipping-rate',
				body: { package_id: 0, rate_id: 'free_shipping:2' },
			},
		] );

		await respondToBatch( 1, [
			batchItem( { cart: 'after-item' } ),
			batchItem( { cart: 'after-rate' } ),
		] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'after-item' },
		} );
		await expect( third ).resolves.toMatchObject( {
			response: { cart: 'after-rate' },
		} );
	} );

	it( 'sends at most 25 writes in one batch', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		for ( let id = 1; id <= 30; id++ ) {
			void addItem( id );
		}
		await respondToBatch( 0, [ batchItem( {} ) ] );

		expect( batchRequests( 1 ) ).toHaveLength( 25 );

		await respondToBatch(
			1,
			Array.from( { length: 25 }, () => batchItem( {} ) )
		);

		expect( batchRequests( 2 ) ).toHaveLength( 5 );
		expect( batchRequests( 2 )[ 0 ].body ).toEqual( {
			id: 26,
			quantity: 1,
		} );
	} );

	it( 'waits for the batching window before sending writes that can be batched', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 299 );
		expect( fetches ).toHaveLength( 0 );
		await tick( 1 );
		expect( fetches ).toHaveLength( 1 );
	} );

	it( 'sends a checkout update on its own, in the order it was made', async () => {
		void selectShippingRate( 'flat_rate:1' );
		const checkoutUpdate = updateDraftOrder( 'cod' );
		void selectShippingRate( 'free_shipping:2' );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );
		expect( batchRequests( 0 ) ).toHaveLength( 1 );

		await respondToBatch( 0, [ batchItem( {} ) ] );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options ).toMatchObject( {
			path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
			method: 'PUT',
			data: { payment_method: 'cod' },
			parse: false,
		} );

		await respondToSingle( 1, { payment_method: 'cod' } );
		await expect( checkoutUpdate ).resolves.toMatchObject( {
			response: { payment_method: 'cod' },
		} );

		expect( fetches ).toHaveLength( 3 );
		expect( batchRequests( 2 ) ).toEqual( [
			{
				path: '/wc/store/v1/cart/select-shipping-rate',
				body: { package_id: 0, rate_id: 'free_shipping:2' },
			},
		] );
	} );

	it( 'queues writes to any route, not only cart and checkout routes', async () => {
		void updateItem( 'item-1', 2 );
		await tick();
		const otherRoute = apiFetchWithHeaders( {
			path: '/wc/store/v1/some-extension/route',
			method: 'POST',
			data: {},
		} );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );

		await respondToSingle( 0, {} );

		expect( fetches ).toHaveLength( 2 );
		expect( batchRequests( 1 ) ).toEqual( [
			{ path: '/wc/store/v1/some-extension/route', body: {} },
		] );

		await respondToBatch( 1, [ batchItem( { ok: true } ) ] );
		await expect( otherRoute ).resolves.toMatchObject( {
			response: { ok: true },
		} );
	} );

	it( 'queues shopper-lists writes and sends them on their own', async () => {
		void updateItem( 'item-1', 2 );
		await tick();
		void apiFetchWithHeaders( {
			path: '/wc/store/v1/shopper-lists/saved-for-later/items',
			method: 'POST',
			data: { key: 'item-2' },
		} );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );

		await respondToSingle( 0, {} );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options ).toMatchObject( {
			path: '/wc/store/v1/shopper-lists/saved-for-later/items',
			parse: false,
		} );
	} );

	it( 'resolves or rejects each write in a batch with its own response', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const succeeds = addItem( 1 );
		const fails = addItem( 99 );
		await respondToBatch( 0, [ batchItem( {} ) ] );

		const error = { code: 'woocommerce_rest_cart_invalid_quantity' };
		await respondToBatch( 1, [
			batchItem( { cart: 'ok' } ),
			batchItem( error, 400 ),
		] );

		await expect( succeeds ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
		await expect( fails ).rejects.toEqual( error );
	} );

	it( 'settles the successful writes in a batch before the failed ones', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const order: string[] = [];
		addItem( 1 ).catch( () => order.push( 'failed' ) );
		void addItem( 2 ).then( () => order.push( 'succeeded' ) );
		await respondToBatch( 0, [ batchItem( {} ) ] );

		await respondToBatch( 1, [
			batchItem( { code: 'error' }, 400 ),
			batchItem( { cart: 'ok' } ),
		] );

		expect( order ).toEqual( [ 'succeeded', 'failed' ] );
	} );

	it( 'removes a write from the queue when it is aborted before it is sent', async () => {
		void updateItem( 'item-1', 2 );
		await tick();
		const controller = new AbortController();
		const aborted = updateItem( 'item-2', 3, controller.signal );
		controller.abort();

		await expect( aborted ).rejects.toMatchObject( {
			name: 'AbortError',
		} );

		await respondToSingle( 0, {} );
		await tick( 300 );
		expect( fetches ).toHaveLength( 1 );
	} );

	it( 'never sends a write whose signal was already aborted', async () => {
		const controller = new AbortController();
		controller.abort();

		await expect(
			updateItem( 'item-1', 2, controller.signal )
		).rejects.toMatchObject( { name: 'AbortError' } );

		await tick( 300 );
		expect( fetches ).toHaveLength( 0 );
	} );

	it( 'rejects an aborted write that was already sent, but waits for the server before sending the next one', async () => {
		const controller = new AbortController();
		const aborted = updateItem( 'item-1', 2, controller.signal );
		await tick();
		expect( fetches ).toHaveLength( 1 );

		controller.abort();
		await expect( aborted ).rejects.toMatchObject( {
			name: 'AbortError',
		} );

		const next = updateItem( 'item-1', 3 );
		await tick( 300 );
		expect( fetches ).toHaveLength( 1 );

		await respondToSingle( 0, { quantity: 2 } );
		expect( fetches ).toHaveLength( 2 );

		await respondToSingle( 1, { quantity: 3 } );
		await expect( next ).resolves.toMatchObject( {
			response: { quantity: 3 },
		} );
	} );

	it( 'ignores the signal of writes that can be batched', async () => {
		const controller = new AbortController();
		const selection = apiFetchWithHeaders( {
			path: '/wc/store/v1/cart/select-shipping-rate',
			method: 'POST',
			data: { package_id: 0, rate_id: 'flat_rate:1' },
			signal: controller.signal,
		} );
		controller.abort();
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );
		await respondToBatch( 0, [ batchItem( { cart: 'ok' } ) ] );
		await expect( selection ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'does not send the signal to the server request', async () => {
		const controller = new AbortController();
		void updateDraftOrder( 'cod' );
		void apiFetchWithHeaders( {
			path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
			method: 'PUT',
			data: { order_notes: 'Leave at the door' },
			signal: controller.signal,
		} );
		await tick();
		await respondToSingle( 0, {} );

		expect( fetches[ 1 ].options ).not.toHaveProperty( 'signal' );
	} );

	it( 'keeps sending writes after a batch request fails', async () => {
		const first = selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const second = selectShippingRate( 'free_shipping:2' );

		fetches[ 0 ].reject( new Error( 'Network error' ) );
		await settle();
		await expect( first ).rejects.toThrow( 'Network error' );

		expect( fetches ).toHaveLength( 2 );
		await respondToBatch( 1, [ batchItem( { cart: 'ok' } ) ] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'does not hold back reads while a write is running', async () => {
		void updateItem( 'item-1', 2 );
		await tick();
		void apiFetchWithHeaders( {
			path: '/wc/store/v1/cart',
			method: 'GET',
		} );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options ).toMatchObject( {
			path: '/wc/store/v1/cart',
			method: 'GET',
		} );
	} );

	it( 'keeps sending writes after a request rejects without an error', async () => {
		const first = updateDraftOrder( 'cod' );
		await tick();
		const second = selectShippingRate( 'flat_rate:1' );

		fetches[ 0 ].reject( undefined );
		await settle();
		await expect( first ).rejects.toBeUndefined();

		expect( fetches ).toHaveLength( 2 );
		await respondToBatch( 1, [ batchItem( { cart: 'ok' } ) ] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'keeps sending writes after a batch request rejects without an error', async () => {
		const first = selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const second = selectShippingRate( 'free_shipping:2' );

		fetches[ 0 ].reject( null );
		await settle();
		await expect( first ).rejects.toBeNull();

		expect( fetches ).toHaveLength( 2 );
		await respondToBatch( 1, [ batchItem( { cart: 'ok' } ) ] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'rejects a write whose request fails before it is sent, and keeps sending writes', async () => {
		const { default: triggerFetch } =
			await import( '@wordpress/api-fetch' );
		const middlewareError = new Error( 'Middleware failed' );
		jest.mocked( triggerFetch )
			.mockImplementationOnce( () => {
				throw middlewareError;
			} )
			.mockImplementationOnce( () => {
				throw middlewareError;
			} );

		const checkoutUpdate = updateDraftOrder( 'cod' );
		const cartUpdate = selectShippingRate( 'flat_rate:1' );
		await tick();
		await expect( checkoutUpdate ).rejects.toBe( middlewareError );
		await tick();
		await expect( cartUpdate ).rejects.toBe( middlewareError );

		const next = selectShippingRate( 'free_shipping:2' );
		await tick( 300 );
		expect( fetches ).toHaveLength( 1 );
		await respondToBatch( 0, [ batchItem( { cart: 'ok' } ) ] );
		await expect( next ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'keeps sending writes when handling the headers of an error response throws', async () => {
		const { default: triggerFetch } =
			await import( '@wordpress/api-fetch' );
		const setNonce = ( triggerFetch as unknown as { setNonce: jest.Mock } )
			.setNonce;
		const headerError = new Error( 'setNonce failed' );
		setNonce.mockImplementationOnce( () => {
			throw headerError;
		} );

		const first = updateDraftOrder( 'cod' );
		await tick();
		const second = updateDraftOrder( 'bacs' );

		fetches[ 0 ].reject( {
			headers: new Headers(),
			json: () => Promise.resolve( { code: 'error' } ),
		} );
		await settle();
		await expect( first ).rejects.toBe( headerError );

		expect( fetches ).toHaveLength( 2 );
		await respondToSingle( 1, { payment_method: 'bacs' } );
		await expect( second ).resolves.toMatchObject( {
			response: { payment_method: 'bacs' },
		} );
	} );

	it( 'keeps sending writes when handling the headers of a failed batch entry throws', async () => {
		const { default: triggerFetch } =
			await import( '@wordpress/api-fetch' );
		const setNonce = ( triggerFetch as unknown as { setNonce: jest.Mock } )
			.setNonce;
		const headerError = new Error( 'setNonce failed' );
		setNonce.mockImplementationOnce( () => {
			throw headerError;
		} );

		const first = selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const second = selectShippingRate( 'free_shipping:2' );

		await respondToBatch( 0, [ batchItem( { code: 'error' }, 400 ) ] );
		await expect( first ).rejects.toBe( headerError );

		expect( fetches ).toHaveLength( 2 );
		await respondToBatch( 1, [ batchItem( { cart: 'ok' } ) ] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );
} );
