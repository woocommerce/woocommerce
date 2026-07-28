/**
 * Tests for the order meta box shipping method name synchronization.
 */

global.jQuery = jest.fn();

const { getShippingMethodTitle } = require( '../meta-boxes-order' );

describe( 'Order meta box shipping method names', () => {
	test( 'uses the selected method title for the default shipping name', () => {
		expect( getShippingMethodTitle( {
			currentTitle: 'Shipping',
			defaultTitle: 'Shipping',
			previousTitle: 'N/A',
			methodValue: 'free_shipping',
			methodTitle: 'Free shipping',
		} ) ).toBe( 'Free shipping' );
	} );

	test.each( [
		[ 'N/A', '', 'N/A' ],
		[ 'Other', 'other', 'Other' ],
	] )( 'resets an auto-synced name when selecting %s', ( label, methodValue, methodTitle ) => {
		expect( getShippingMethodTitle( {
			currentTitle: 'Free shipping',
			defaultTitle: 'Shipping',
			previousTitle: 'Free shipping',
			methodValue,
			methodTitle,
		} ) ).toBe( 'Shipping' );
	} );

	test( 'preserves a custom name when selecting N/A', () => {
		expect( getShippingMethodTitle( {
			currentTitle: 'Local courier',
			defaultTitle: 'Shipping',
			previousTitle: 'Free shipping',
			methodValue: '',
			methodTitle: 'N/A',
		} ) ).toBe( 'Local courier' );
	} );
} );
