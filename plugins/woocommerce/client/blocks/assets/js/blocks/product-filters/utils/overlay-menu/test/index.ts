/**
 * Internal dependencies
 */
import { getOverlayMenu } from '../';

describe( 'getOverlayMenu', () => {
	it( 'defaults to mobile', () => {
		expect( getOverlayMenu( { isPreview: false } ) ).toBe( 'mobile' );
	} );

	it( 'returns explicit overlay menu settings', () => {
		expect(
			getOverlayMenu( { isPreview: false, overlayMenu: 'always' } )
		).toBe( 'always' );
		expect(
			getOverlayMenu( { isPreview: false, overlayMenu: 'never' } )
		).toBe( 'never' );
	} );

	it( 'maps the legacy disabled drawer setting to never', () => {
		expect(
			getOverlayMenu( {
				isPreview: false,
				overlayMenu: 'mobile',
				showFilterDrawer: false,
			} )
		).toBe( 'never' );
	} );

	it( 'prioritizes explicit non-default overlay settings over legacy settings', () => {
		expect(
			getOverlayMenu( {
				isPreview: false,
				overlayMenu: 'always',
				showFilterDrawer: false,
			} )
		).toBe( 'always' );
	} );
} );
