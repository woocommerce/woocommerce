/**
 * External dependencies
 */
import assert from 'node:assert/strict';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { test } from 'node:test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { createControlRequestContext } from './request-utils/control-context.ts';

test( 'routes RequestUtils REST calls through one disposable Blocks control context', async () => {
	const requests = [];
	const socketIds = new Map();
	let responseCount = 0;
	const server = createServer( async ( request, response ) => {
		let socketId = socketIds.get( request.socket );
		if ( socketId === undefined ) {
			socketId = socketIds.size + 1;
			socketIds.set( request.socket, socketId );
		}

		const bodyChunks = [];
		for await ( const chunk of request ) {
			bodyChunks.push( chunk );
		}

		requests.push( {
			body: JSON.parse( Buffer.concat( bodyChunks ).toString( 'utf8' ) ),
			connection: request.headers.connection,
			cookie: request.headers.cookie,
			method: request.method,
			nonce: request.headers[ 'x-wp-nonce' ],
			path: request.url,
			socketId,
		} );

		response.writeHead( 201, { 'Content-Type': 'application/json' } );
		response.end( JSON.stringify( { request: requests.length } ), () => {
			responseCount++;
		} );
	} );
	const temporaryDirectory = await mkdtemp(
		path.join( tmpdir(), 'wc-blocks-control-context-' )
	);
	const storageStatePath = path.join( temporaryDirectory, 'admin.json' );
	let requestContext;
	let requestContextDisposed = false;

	try {
		await new Promise( ( resolve, reject ) => {
			server.once( 'error', reject );
			server.listen( 0, '127.0.0.1', resolve );
		} );
		const address = server.address();
		assert.notEqual( address, null );
		assert.equal( typeof address, 'object' );
		const baseURL = `http://127.0.0.1:${ address.port }`;
		const customStorageState = {
			cookies: [
				{
					name: 'wordpress_logged_in',
					value: 'test-session',
					domain: '127.0.0.1',
					path: '/',
					expires: -1,
					httpOnly: true,
					secure: false,
					sameSite: 'Lax',
				},
			],
			nonce: 'test-nonce',
			rootURL: `${ baseURL }/wp-json/`,
		};
		await writeFile(
			storageStatePath,
			JSON.stringify( customStorageState ),
			'utf8'
		);

		const controlContext = await createControlRequestContext( {
			baseURL,
			storageStatePath,
		} );
		requestContext = controlContext.requestContext;

		assert.deepEqual( controlContext.storageState, customStorageState );
		const requestUtils = new RequestUtils( requestContext, {
			baseURL,
			storageState: controlContext.storageState,
			storageStatePath,
		} );
		const companyOptionRequest = {
			method: 'POST',
			path: 'e2e-options/update',
			data: {
				option_name: 'woocommerce_checkout_company_field',
				option_value: 'hidden',
			},
		};

		const responsePayloads = [
			await requestUtils.rest( companyOptionRequest ),
			await requestUtils.rest( companyOptionRequest ),
		];

		assert.deepEqual( responsePayloads, [
			{ request: 1 },
			{ request: 2 },
		] );
		assert.equal( requests.length, 2 );
		assert.equal( responseCount, 2 );
		assert.deepEqual(
			requests.map( ( request ) => request.method ),
			[ 'POST', 'POST' ]
		);
		assert.deepEqual(
			requests.map( ( request ) => request.path ),
			[ '/wp-json/e2e-options/update', '/wp-json/e2e-options/update' ]
		);
		assert.deepEqual(
			requests.map( ( request ) => request.body ),
			[
				{
					option_name: 'woocommerce_checkout_company_field',
					option_value: 'hidden',
				},
				{
					option_name: 'woocommerce_checkout_company_field',
					option_value: 'hidden',
				},
			]
		);
		assert.deepEqual(
			requests.map( ( request ) => request.connection ),
			[ 'close', 'close' ]
		);
		assert.equal(
			new Set( requests.map( ( request ) => request.socketId ) ).size,
			2
		);
		assert.deepEqual(
			requests.map( ( request ) => request.cookie ),
			[
				'wordpress_logged_in=test-session',
				'wordpress_logged_in=test-session',
			]
		);
		assert.deepEqual(
			requests.map( ( request ) => request.nonce ),
			[ 'test-nonce', 'test-nonce' ]
		);

		await requestContext.dispose();
		requestContextDisposed = true;
		await assert.rejects( requestUtils.rest( companyOptionRequest ) );
	} finally {
		if ( requestContext && ! requestContextDisposed ) {
			await requestContext.dispose();
		}
		await new Promise( ( resolve, reject ) => {
			server.close( ( error ) => {
				if ( error ) {
					reject( error );
					return;
				}
				resolve();
			} );
		} );
		await rm( temporaryDirectory, { recursive: true, force: true } );
	}
} );
