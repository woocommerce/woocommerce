/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types';

describe( 'type-guards', () => {
	describe( 'Testing isObject()', () => {
		it( 'Correctly identifies an object', () => {
			expect( isObject( {} ) ).toBe( true );
			expect( isObject( { test: 'object' } ) ).toBe( true );
		} );
		it( 'Correctly rejects object-like things', () => {
			expect( isObject( [] ) ).toBe( false );
			expect( isObject( null ) ).toBe( false );
		} );
	} );
} );
