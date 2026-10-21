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

const updateDraftOrder = ( paymentMethod: string ) =>
	apiFetchWithHeaders( {
		path: '/wc/store/v1/checkout?__experimental_calc_totals=true',
		method: 'PUT',
		data: { payment_method: paymentMethod },
	} );

describe( 'apiFetchWithHeaders session writes', () => {
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

	it( 'sends a session write only after the server has finished the one before it', async () => {
		const first = updateItem( 'item-1', 2 );
		await tick();
		const second = updateItem( 'item-2', 3 );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );
		expect( batchRequests( 0 ) ).toEqual( [
			{
				path: '/wc/store/v1/cart/update-item',
				body: { key: 'item-1', quantity: 2 },
			},
		] );

		await respondToBatch( 0, [ batchItem( { items: 'first' } ) ] );
		await expect( first ).resolves.toEqual( {
			response: { items: 'first' },
			headers: {},
		} );

		expect( fetches ).toHaveLength( 2 );
		expect( batchRequests( 1 ) ).toEqual( [
			{
				path: '/wc/store/v1/cart/update-item',
				body: { key: 'item-2', quantity: 3 },
			},
		] );

		await respondToBatch( 1, [ batchItem( { items: 'second' } ) ] );
		await expect( second ).resolves.toEqual( {
			response: { items: 'second' },
			headers: {},
		} );
	} );

	it( 'sends the writes made while one is running together in one batch', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const second = updateItem( 'item-1', 2 );
		const third = selectShippingRate( 'free_shipping:2' );
		await tick( 300 );

		expect( fetches ).toHaveLength( 1 );

		await respondToBatch( 0, [ batchItem( {} ) ] );

		expect( fetches ).toHaveLength( 2 );
		expect( fetches[ 1 ].options.path ).toBe( '/wc/store/v1/batch' );
		expect( batchRequests( 1 ) ).toEqual( [
			{
				path: '/wc/store/v1/cart/update-item',
				body: { key: 'item-1', quantity: 2 },
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

	it( 'waits for the batching window before sending writes that were always batched', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 299 );
		expect( fetches ).toHaveLength( 0 );
		await tick( 1 );
		expect( fetches ).toHaveLength( 1 );
	} );

	it( 'sends a checkout update on its own, in the order it was made', async () => {
		void updateItem( 'item-1', 2 );
		const checkoutUpdate = updateDraftOrder( 'cod' );
		void updateItem( 'item-2', 3 );
		await tick();

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
				path: '/wc/store/v1/cart/update-item',
				body: { key: 'item-2', quantity: 3 },
			},
		] );
	} );

	it( 'resolves or rejects each write in a batch with its own response', async () => {
		void selectShippingRate( 'flat_rate:1' );
		await tick( 300 );
		const succeeds = updateItem( 'item-1', 2 );
		const fails = updateItem( 'item-2', 99 );
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

	it( 'removes a write from the queue when it is aborted before it is sent', async () => {
		void updateItem( 'item-1', 2 );
		await tick();
		const controller = new AbortController();
		const aborted = updateItem( 'item-2', 3, controller.signal );
		controller.abort();

		await expect( aborted ).rejects.toMatchObject( {
			name: 'AbortError',
		} );

		await respondToBatch( 0, [ batchItem( {} ) ] );
		await tick( 300 );
		expect( fetches ).toHaveLength( 1 );
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

		await respondToBatch( 0, [ batchItem( { quantity: 2 } ) ] );
		expect( fetches ).toHaveLength( 2 );

		await respondToBatch( 1, [ batchItem( { quantity: 3 } ) ] );
		await expect( next ).resolves.toMatchObject( {
			response: { quantity: 3 },
		} );
	} );

	it( 'ignores the signal of writes that were always batched', async () => {
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
		const first = updateItem( 'item-1', 2 );
		await tick();
		const second = updateItem( 'item-2', 3 );

		fetches[ 0 ].reject( new Error( 'Network error' ) );
		await settle();
		await expect( first ).rejects.toThrow( 'Network error' );

		expect( fetches ).toHaveLength( 2 );
		await respondToBatch( 1, [ batchItem( { cart: 'ok' } ) ] );
		await expect( second ).resolves.toMatchObject( {
			response: { cart: 'ok' },
		} );
	} );

	it( 'does not hold back reads while a session write is running', async () => {
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
} );
