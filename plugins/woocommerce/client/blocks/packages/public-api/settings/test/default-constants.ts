/**
 * Internal dependencies
 */
import { CURRENT_SITE_ID } from '../default-constants';

describe( 'default constants', () => {
	// No `window.wcSettings` under test, so this is what a consumer reads when
	// the payload never arrived. Not 1: that is a real blog ID, and a multisite
	// child would address the main site's per-site state with it.
	it( 'CURRENT_SITE_ID falls back to 0, which is not a usable blog ID', () => {
		expect( CURRENT_SITE_ID ).toBe( 0 );
	} );
} );
