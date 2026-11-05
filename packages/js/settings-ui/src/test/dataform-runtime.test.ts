/**
 * Internal dependencies
 */
import * as SettingsUI from '../index';

describe( 'DataForm runtime', () => {
	it( 'does not become a package export', () => {
		expect( SettingsUI ).not.toHaveProperty( 'DataForm' );
	} );
} );
