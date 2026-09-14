/**
 * Smoke tests for @woocommerce/blocks-checkout public exports.
 *
 * These tests verify that the barrel file correctly re-exports
 * paymentMethodCommonIcons and getCommonIconProps so that third-party
 * extensions can import them from the package entry point.
 */

/**
 * External dependencies
 */
import {
	paymentMethodCommonIcons,
	getCommonIconProps,
} from '@woocommerce/blocks-checkout';

describe( '@woocommerce/blocks-checkout payment icon exports', () => {
	it( 'exports paymentMethodCommonIcons as a non-empty array', () => {
		expect( Array.isArray( paymentMethodCommonIcons ) ).toBe( true );
		expect( paymentMethodCommonIcons.length ).toBeGreaterThan( 0 );
	} );

	it( 'exports getCommonIconProps as a function', () => {
		expect( typeof getCommonIconProps ).toBe( 'function' );
	} );

	it( 'getCommonIconProps returns Visa icon props from the package export', () => {
		const result = getCommonIconProps( 'visa' );
		expect( result ).toEqual(
			expect.objectContaining( {
				id: 'visa',
				alt: 'Visa',
			} )
		);
	} );

	it( 'getCommonIconProps returns empty object for unknown brand from the package export', () => {
		const result = getCommonIconProps( 'nonexistent' );
		expect( result ).toEqual( {} );
	} );
} );
