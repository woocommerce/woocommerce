/**
 * Mock Blackbox SDK
 * Provides window.Blackbox.init() that returns Promise<session_id>
 *
 * This is a PoC mock that simulates the Blackbox SDK behavior.
 * In production, this would be replaced by the actual Blackbox SDK.
 */
( function () {
	'use strict';

	const INIT_DELAY_MS = 1000;

	const generateSessionId = () => {
		return `bb_${ Date.now().toString( 36 ) }_${ Math.random()
			.toString( 36 )
			.substring( 2, 15 ) }`;
	};

	window.Blackbox = {
		init: () => {
			return new Promise( ( resolve ) => {
				// eslint-disable-next-line no-console
				console.log( '[Blackbox Mock] Initializing...' );
				setTimeout( () => {
					const sessionId = generateSessionId();
					// eslint-disable-next-line no-console
					console.log( '[Blackbox Mock] Session ID:', sessionId );
					resolve( sessionId );
				}, INIT_DELAY_MS );
			} );
		},
	};

	// eslint-disable-next-line no-console
	console.log( '[Blackbox Mock] SDK loaded' );
} )();
