/**
 * Tests for the Flat Config export of @woocommerce/eslint-plugin.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/65458
 */

'use strict';

const flatRecommended = require( '../recommended' );
const legacyRecommended = require( '../../recommended' );

describe( 'Flat Config export', () => {
	test( 'exports an array suitable for spreading into a flat config', () => {
		expect( Array.isArray( flatRecommended ) ).toBe( true );
		expect( flatRecommended.length ).toBeGreaterThan( 0 );
	} );

	test( 'each entry is a flat-config object (no eslintrc-only `extends` key)', () => {
		flatRecommended.forEach( ( entry ) => {
			expect( typeof entry ).toBe( 'object' );
			expect( entry ).not.toBeNull();
			// eslintrc-style `extends` is illegal in flat config; FlatCompat
			// resolves it into the entry's `rules` / `plugins` / etc.
			expect( entry ).not.toHaveProperty( 'extends' );
		} );
	} );

	test( 'still ships the legacy eslintrc config object unchanged', () => {
		// Regression guard: the 73 monorepo consumers depend on this shape.
		expect( Array.isArray( legacyRecommended ) ).toBe( false );
		expect( typeof legacyRecommended ).toBe( 'object' );
		expect( Array.isArray( legacyRecommended.extends ) ).toBe( true );
	} );

	test( 'the issue reproducer can spread the export without throwing', () => {
		// This is the exact pattern from issue #65458, translated to use
		// the new key. Before the fix this throws "TypeError: ... is not
		// iterable" because `configs.recommended` is a plain object.
		expect( () => [ ...flatRecommended ] ).not.toThrow();
	} );
} );
