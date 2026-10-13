/**
 * External dependencies
 */
import { afterEach, describe, expect, it } from '@jest/globals';

/**
 * Internal dependencies
 */
import { loadTrackingScript } from '../tracking-script';

const TRACKING_SCRIPT_URL = 'https://stats.wp.com/s-202642.js';

const getTrackingScripts = () =>
	document.querySelectorAll< HTMLScriptElement >( `script[src="${ TRACKING_SCRIPT_URL }"]` );

describe( 'loadTrackingScript', () => {
	afterEach( () => {
		document.head.replaceChildren();
	} );

	it( 'adds s.js as an async script', () => {
		loadTrackingScript( TRACKING_SCRIPT_URL );

		const scripts = getTrackingScripts();
		expect( scripts ).toHaveLength( 1 );
		expect( scripts[ 0 ].async ).toBe( true );
	} );

	it( 'adds s.js only once', () => {
		loadTrackingScript( TRACKING_SCRIPT_URL );
		loadTrackingScript( TRACKING_SCRIPT_URL );

		expect( getTrackingScripts() ).toHaveLength( 1 );
	} );
} );
