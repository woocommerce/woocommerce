/**
 * External dependencies
 */
import { afterEach, describe, expect, it } from '@jest/globals';

/**
 * Internal dependencies
 */
import { activateTrackingScript } from '../tracking-script';

const TRACKING_SCRIPT_URL = 'https://stats.wp.com/s-202642.js';

function printHeldTag( attributes: Record< string, string > = { src: TRACKING_SCRIPT_URL } ) {
	const held = document.createElement( 'script' );
	held.setAttribute( 'type', 'text/plain' );
	held.setAttribute( 'id', 'woocommerce-analytics-js' );
	held.setAttribute( 'data-wc-analytics-consent', 'statistics' );
	for ( const [ name, value ] of Object.entries( attributes ) ) {
		held.setAttribute( name, value );
	}
	document.head.appendChild( held );
}

const getScripts = () => Array.from( document.querySelectorAll( 'script' ) );

describe( 'activateTrackingScript', () => {
	afterEach( () => {
		document.head.replaceChildren();
	} );

	it( 'replaces the held s.js tag with one that runs, keeping its attributes', () => {
		printHeldTag( { src: TRACKING_SCRIPT_URL, crossorigin: 'anonymous' } );

		activateTrackingScript();

		const scripts = getScripts();
		expect( scripts ).toHaveLength( 1 );
		expect( scripts[ 0 ].getAttribute( 'type' ) ).toBeNull();
		expect( scripts[ 0 ].hasAttribute( 'data-wc-analytics-consent' ) ).toBe( false );
		expect( scripts[ 0 ].src ).toBe( TRACKING_SCRIPT_URL );
		expect( scripts[ 0 ].id ).toBe( 'woocommerce-analytics-js' );
		expect( scripts[ 0 ].getAttribute( 'crossorigin' ) ).toBe( 'anonymous' );
	} );

	it( 'keeps the nonce of the held tag', () => {
		printHeldTag();
		// Browsers hide the nonce attribute once the tag is parsed, leaving it only on the property.
		Object.defineProperty( document.querySelector( 'script' )!, 'nonce', { value: 'abc123' } );

		activateTrackingScript();

		expect( getScripts()[ 0 ].nonce ).toBe( 'abc123' );
	} );

	it( 'adds s.js only once', () => {
		printHeldTag();

		activateTrackingScript();
		activateTrackingScript();

		expect( getScripts() ).toHaveLength( 1 );
	} );

	it( 'leaves a held tag alone once a consent plugin has moved its src', () => {
		printHeldTag( { 'data-cmplz-src': TRACKING_SCRIPT_URL } );

		activateTrackingScript();

		const scripts = getScripts();
		expect( scripts ).toHaveLength( 1 );
		expect( scripts[ 0 ].getAttribute( 'type' ) ).toBe( 'text/plain' );
	} );

	it.each( [
		'https://evil.example/s-202642.js',
		'https://stats.wp.com/other.js',
		'http://stats.wp.com/s-202642.js',
		'javascript:alert(1)',
	] )( 'never runs a held tag whose src is not s.js: %s', src => {
		printHeldTag( { src } );

		activateTrackingScript();

		const scripts = getScripts();
		expect( scripts ).toHaveLength( 1 );
		expect( scripts[ 0 ].getAttribute( 'type' ) ).toBe( 'text/plain' );
	} );
} );
