/**
 * Internal dependencies
 */
import { getAutoCompleteValue } from '../utils';

describe( 'getAutoCompleteValue', () => {
	it( 'prefixes billing and shipping fields with the section and address type', () => {
		expect( getAutoCompleteValue( 'address-line1', 'billing' ) ).toBe(
			'section-billing billing address-line1'
		);
		expect( getAutoCompleteValue( 'address-line1', 'shipping' ) ).toBe(
			'section-shipping shipping address-line1'
		);
	} );

	it( 'leaves the value bare for address types the autofill grammar does not allow', () => {
		expect( getAutoCompleteValue( 'email', 'contact' ) ).toBe( 'email' );
		expect( getAutoCompleteValue( 'email', 'order' ) ).toBe( 'email' );
		expect( getAutoCompleteValue( 'email', 'anything-else' ) ).toBe(
			'email'
		);
	} );

	it( 'passes on and off through untouched', () => {
		expect( getAutoCompleteValue( 'off', 'billing' ) ).toBe( 'off' );
		expect( getAutoCompleteValue( 'on', 'shipping' ) ).toBe( 'on' );
		expect( getAutoCompleteValue( 'OFF', 'billing' ) ).toBe( 'OFF' );
		expect( getAutoCompleteValue( ' off ', 'billing' ) ).toBe( 'off' );
	} );

	it( 'only matches on and off exactly, not values that begin with them', () => {
		expect( getAutoCompleteValue( 'organization', 'billing' ) ).toBe(
			'section-billing billing organization'
		);
		expect( getAutoCompleteValue( 'one-time-code', 'billing' ) ).toBe(
			'section-billing billing one-time-code'
		);
	} );

	it( 'leaves a value that already carries its own tokens alone', () => {
		// Prefixing would push it past the token limit and the browser would
		// drop the whole hint.
		expect(
			getAutoCompleteValue( 'shipping address-line1', 'billing' )
		).toBe( 'shipping address-line1' );
		expect(
			getAutoCompleteValue( 'section-work address-line2', 'billing' )
		).toBe( 'section-work address-line2' );
	} );

	it( 'normalises the address type it puts in the attribute', () => {
		expect( getAutoCompleteValue( 'address-line1', ' Billing ' ) ).toBe(
			'section-billing billing address-line1'
		);
	} );

	it( 'returns undefined when the field has no autofill hint', () => {
		expect( getAutoCompleteValue( undefined, 'billing' ) ).toBeUndefined();
		expect( getAutoCompleteValue( '', 'billing' ) ).toBeUndefined();
		expect( getAutoCompleteValue( '   ', 'billing' ) ).toBeUndefined();
	} );

	it( 'survives a registered field supplying a non-string value', () => {
		// woocommerce_register_additional_checkout_field() keeps unknown option
		// values verbatim, so these reach the browser as-is.
		const nonStrings = [ 123, true, [ 'address-line1' ], { a: 1 }, null ];

		nonStrings.forEach( ( autocomplete ) => {
			expect(
				getAutoCompleteValue(
					autocomplete as unknown as string,
					'billing'
				)
			).toBeUndefined();
		} );

		expect( getAutoCompleteValue( 'email', 0 as unknown as string ) ).toBe(
			'email'
		);
	} );
} );
