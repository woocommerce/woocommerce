/**
 * Internal dependencies
 */
import { isValidSlug } from '../utils';

describe( 'product section utils', () => {
	describe( 'isValidSlug', () => {
		it( 'should return true if a slug is valid', () => {
			const validity = isValidSlug( 'valid-slug_123' );
			expect( validity ).toBeTruthy();
		} );

		it( 'should return false if a slug contains spaces', () => {
			const validity = isValidSlug( 'Invalid slug' );
			expect( validity ).toBeFalsy();
		} );

		it( 'should return false if invalid characters exist', () => {
			const validity = isValidSlug( 'invalid-characters!' );
			expect( validity ).toBeFalsy();
		} );
	} );
} );
