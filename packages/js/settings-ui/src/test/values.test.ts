import { valueMatchesVisibilityRule } from '../values';

describe( 'valueMatchesVisibilityRule', () => {
	it( 'uses true only when the rule value is absent', () => {
		expect( valueMatchesVisibilityRule( true, undefined ) ).toBe( true );
		expect( valueMatchesVisibilityRule( false, undefined ) ).toBe( false );
	} );

	it( 'matches an explicit null rule value', () => {
		expect( valueMatchesVisibilityRule( null, null ) ).toBe( true );
		expect( valueMatchesVisibilityRule( true, null ) ).toBe( false );
	} );
} );
