/**
 * Internal dependencies
 */
import variations from '../variations';

describe( 'variations autocompleter', () => {
	const originalWcSettings = window.wcSettings;

	afterEach( () => {
		window.wcSettings = originalWcSettings;
	} );

	it( 'does not throw when wcSettings is undefined (third-party context)', () => {
		// Simulate a third-party plugin context where wcSettings is not set.
		delete ( window as { wcSettings?: unknown } ).wcSettings;

		expect( () =>
			variations.getOptionKeywords( {
				id: 1,
				name: 'My Variation',
				attributes: [ { option: 'Red' }, { option: 'Large' } ],
				sku: 'SKU-1',
			} )
		).not.toThrow();
	} );

	it( 'uses the default separator when wcSettings is undefined', () => {
		delete ( window as { wcSettings?: unknown } ).wcSettings;

		const keywords = variations.getOptionKeywords( {
			id: 1,
			name: 'My Variation',
			attributes: [ { option: 'Red' }, { option: 'Large' } ],
			sku: 'SKU-1',
		} );

		// First keyword is the formatted variation name using default separator " - ".
		expect( keywords[ 0 ] ).toBe( 'My Variation - Red, Large' );
		expect( keywords[ 1 ] ).toBe( 'SKU-1' );
	} );

	it( 'uses the configured separator when wcSettings provides one', () => {
		window.wcSettings = {
			variationTitleAttributesSeparator: ' | ',
			countries: {},
		};

		const keywords = variations.getOptionKeywords( {
			id: 1,
			name: 'My Variation',
			attributes: [ { option: 'Red' } ],
			sku: 'SKU-1',
		} );

		expect( keywords[ 0 ] ).toBe( 'My Variation | Red' );
	} );
} );
