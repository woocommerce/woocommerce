/**
 * Internal dependencies
 */
import { commonIcons, getCommonIconProps } from '../common-icons';

describe( 'common-icons exports', () => {
	describe( 'commonIcons', () => {
		it( 'is an array of icon definitions', () => {
			expect( Array.isArray( commonIcons ) ).toBe( true );
			expect( commonIcons.length ).toBeGreaterThan( 0 );
		} );

		it( 'contains icon definitions with id, alt, and src', () => {
			commonIcons.forEach( ( icon ) => {
				expect( icon ).toHaveProperty( 'id' );
				expect( icon ).toHaveProperty( 'alt' );
				expect( icon ).toHaveProperty( 'src' );
				expect( typeof icon.id ).toBe( 'string' );
				expect( typeof icon.alt ).toBe( 'string' );
				expect( typeof icon.src ).toBe( 'string' );
			} );
		} );

		it( 'includes expected payment method icons', () => {
			const ids = commonIcons.map( ( icon ) => icon.id );
			expect( ids ).toContain( 'visa' );
			expect( ids ).toContain( 'mastercard' );
			expect( ids ).toContain( 'amex' );
		} );
	} );

	describe( 'getCommonIconProps', () => {
		it( 'returns the matching icon props for a known id', () => {
			const result = getCommonIconProps( 'visa' );
			expect( result ).toEqual(
				expect.objectContaining( {
					id: 'visa',
					alt: 'Visa',
				} )
			);
			expect( typeof ( result as { src: string } ).src ).toBe( 'string' );
		} );

		it( 'returns an empty object for an unknown id', () => {
			const result = getCommonIconProps( 'unknown-brand' );
			expect( result ).toEqual( {} );
		} );

		it( 'returns an empty object for an empty string', () => {
			const result = getCommonIconProps( '' );
			expect( result ).toEqual( {} );
		} );
	} );
} );
