/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { EventEmitter } from 'node:events';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { FrontendUtils } from './frontend/frontend-utils.page.ts';

class FakeRequest {
	constructor( url, { method = 'POST', data } = {} ) {
		this.requestUrl = url;
		this.requestMethod = method;
		this.data = data;
	}

	url() {
		return this.requestUrl;
	}

	method() {
		return this.requestMethod;
	}

	postDataJSON() {
		return this.data;
	}
}

class FakePage extends EventEmitter {
	constructor() {
		super();
		this.addAction = async () => {};
		this.removeActions = [];
		this.emptyCartVisible = false;
	}

	async click() {
		await this.addAction();
	}

	async goto() {}

	getByLabel( label ) {
		if ( typeof label === 'string' ) {
			return { click: async () => this.addAction() };
		}

		return {
			count: async () => this.removeActions.length,
			first: () => ( {
				click: async () => {
					const removeAction = this.removeActions.shift();
					assert.notEqual( removeAction, undefined );
					await removeAction();
				},
			} ),
		};
	}

	getByText() {
		return {
			isVisible: async () => this.emptyCartVisible,
			waitFor: async () => {
				assert.equal( this.emptyCartVisible, true );
			},
		};
	}
}

const CART_ADD_URL = 'http://localhost/wp-json/wc/store/v1/cart/add-item';
const CART_REMOVE_URL = 'http://localhost/wp-json/wc/store/v1/cart/remove-item';
const BATCH_URL = 'http://localhost/wp-json/wc/store/v1/batch';

function createFrontendUtils( page ) {
	return new FrontendUtils( page, {} );
}

function assertNoRequestListeners( page ) {
	assert.equal( page.listenerCount( 'request' ), 0 );
	assert.equal( page.listenerCount( 'requestfinished' ), 0 );
	assert.equal( page.listenerCount( 'requestfailed' ), 0 );
}

test( 'emptyCart tracks every removal until its own request settles', async () => {
	const page = new FakePage();
	const firstRequest = new FakeRequest( CART_REMOVE_URL );
	const secondRequest = new FakeRequest( CART_REMOVE_URL );
	let secondRequestObserved;
	const secondRequestStarted = new Promise( ( resolve ) => {
		secondRequestObserved = resolve;
	} );

	page.removeActions.push(
		async () => {
			page.emit( 'request', firstRequest );
			page.emit( 'requestfinished', firstRequest );
		},
		async () => {
			page.emit( 'request', secondRequest );
			page.emptyCartVisible = true;
			secondRequestObserved();
		}
	);

	let emptyCartCompleted = false;
	const emptyCart = createFrontendUtils( page )
		.emptyCart()
		.then( () => {
			emptyCartCompleted = true;
		} );

	await secondRequestStarted;
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( emptyCartCompleted, false );

	page.emit( 'requestfinished', secondRequest );
	await emptyCart;
	assertNoRequestListeners( page );
} );

test( 'addToCart removes request listeners when the click fails', async () => {
	const page = new FakePage();
	page.addAction = async () => {
		throw new Error( 'click failed' );
	};

	await assert.rejects(
		createFrontendUtils( page ).addToCart(),
		/click failed/
	);
	assertNoRequestListeners( page );
} );

test( 'addToCart waits for the action after its requests have already settled', async () => {
	const page = new FakePage();
	const request = new FakeRequest( CART_ADD_URL );
	let finishAction;
	const actionFinished = new Promise( ( resolve ) => {
		finishAction = resolve;
	} );
	let requestsSettled;
	const requestSettlementObserved = new Promise( ( resolve ) => {
		requestsSettled = resolve;
	} );
	page.addAction = async () => {
		page.emit( 'request', request );
		page.emit( 'requestfinished', request );
		requestsSettled();
		await actionFinished;
	};

	let addToCartCompleted = false;
	const addToCart = createFrontendUtils( page )
		.addToCart()
		.then( () => {
			addToCartCompleted = true;
		} );

	await requestSettlementObserved;
	await Promise.resolve();
	assert.equal( addToCartCompleted, false );

	finishAction();
	await addToCart;
	assertNoRequestListeners( page );
} );

test( 'addToCart waits for every same-URL request including failures', async () => {
	const page = new FakePage();
	const firstRequest = new FakeRequest( CART_ADD_URL );
	const secondRequest = new FakeRequest( CART_ADD_URL );
	page.addAction = async () => {
		page.emit( 'request', firstRequest );
		page.emit( 'request', secondRequest );
		page.emit( 'requestfinished', firstRequest );
	};

	let addToCartCompleted = false;
	const addToCart = createFrontendUtils( page )
		.addToCart()
		.then( () => {
			addToCartCompleted = true;
		} );

	await Promise.resolve();
	assert.equal( addToCartCompleted, false );

	page.emit( 'requestfailed', secondRequest );
	await addToCart;
	assertNoRequestListeners( page );
} );

test( 'addToCart ignores reads and a batch without an inner cart write', async ( t ) => {
	t.mock.timers.enable( { apis: [ 'setTimeout' ] } );
	const page = new FakePage();
	const cartRead = new FakeRequest( CART_ADD_URL, { method: 'GET' } );
	const unrelatedBatch = new FakeRequest( BATCH_URL, {
		data: {
			requests: [ { method: 'GET', path: '/wc/store/v1/products' } ],
		},
	} );
	page.addAction = async () => {
		page.emit( 'request', cartRead );
		page.emit( 'requestfinished', cartRead );
		page.emit( 'request', unrelatedBatch );
		page.emit( 'requestfinished', unrelatedBatch );
	};

	let addToCartCompleted = false;
	const addToCart = createFrontendUtils( page )
		.addToCart()
		.then( () => {
			addToCartCompleted = true;
		} );

	await new Promise( ( resolve ) => setImmediate( resolve ) );
	t.mock.timers.tick( 100 );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( addToCartCompleted, false );

	const cartBatch = new FakeRequest( BATCH_URL, {
		data: {
			requests: [
				{
					method: 'POST',
					path: '/wc/store/v1/cart/add-item',
				},
			],
		},
	} );
	page.emit( 'request', cartBatch );
	page.emit( 'requestfinished', cartBatch );
	await addToCart;
	assertNoRequestListeners( page );
} );

test( 'addToCart times out without a matching cart write and cleans up', async ( t ) => {
	t.mock.timers.enable( { apis: [ 'setTimeout' ] } );
	let currentTime = 0;
	t.mock.method( performance, 'now', () => currentTime );
	const page = new FakePage();
	const addToCart = createFrontendUtils( page ).addToCart();

	await new Promise( ( resolve ) => setImmediate( resolve ) );
	currentTime = 5000;
	t.mock.timers.runAll();

	await assert.rejects( addToCart, /waiting for a cart write request/i );
	assertNoRequestListeners( page );
} );
