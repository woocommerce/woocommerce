import { areValuesEqual, valueMatchesVisibilityRule } from '../values';

describe( 'areValuesEqual', () => {
	it( 'compares relative date values by number and unit', () => {
		expect(
			areValuesEqual(
				{ number: 30, unit: 'days' },
				{ number: 30, unit: 'days' }
			)
		).toBe( true );
		expect(
			areValuesEqual(
				{ number: 30, unit: 'days' },
				{ number: 30, unit: 'weeks' }
			)
		).toBe( false );
		expect(
			areValuesEqual(
				{ number: '', unit: 'months' },
				{ number: 30, unit: 'months' }
			)
		).toBe( false );
	} );

	it( 'does not weaken scalar and list equality', () => {
		expect( areValuesEqual( '1', 1 ) ).toBe( false );
		expect( areValuesEqual( [ 'a', 'b' ], [ 'a', 'b' ] ) ).toBe( true );
		expect( areValuesEqual( [ 'a', 'b' ], [ 'b', 'a' ] ) ).toBe( false );
	} );
} );

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
