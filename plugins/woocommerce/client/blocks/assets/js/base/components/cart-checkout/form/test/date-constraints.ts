/**
 * Internal dependencies
 */
import {
	resolveDateConstraint,
	resolveDateConstraints,
} from '../date-constraints';

describe( 'resolveDateConstraint', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	const onDate = ( today: string ) =>
		jest
			.useFakeTimers()
			.setSystemTime( new Date( `${ today }T12:00:00Z` ) );

	it( 'returns undefined when there is no constraint', () => {
		expect( resolveDateConstraint( undefined ) ).toBeUndefined();
	} );

	it.each( [ [ 'not-a-date' ], [ '' ], [ 'today' ], [ '+1 day' ], [ 'P' ] ] )(
		'returns undefined for the unparseable constraint %s',
		( value ) => {
			expect( resolveDateConstraint( value ) ).toBeUndefined();
		}
	);

	it( 'passes an absolute date through', () => {
		expect( resolveDateConstraint( '2026-01-01' ) ).toBe( '2026-01-01' );
	} );

	it.each( [
		[ 'P0D', '2026-08-26' ],
		[ 'P1D', '2026-08-27' ],
		[ '-P5D', '2026-08-21' ],
		[ 'P2W', '2026-09-09' ],
		[ 'P1W2D', '2026-09-04' ],
		[ '-P1W2D', '2026-08-17' ],
		[ 'P1M2W', '2026-10-10' ],
		[ 'P3M', '2026-11-26' ],
		[ '-P18Y', '2008-08-26' ],
		[ 'P1Y2M3D', '2027-10-29' ],
	] )( 'resolves %s to %s', ( constraint, expected ) => {
		onDate( '2026-08-26' );

		expect( resolveDateConstraint( constraint ) ).toBe( expected );
	} );

	// Edge cases in which adding a period to a date should resolve based on the period, not number of dates.
	// For example, adding 1 month to Jan 31 should resolve to Feb 28, not Mar 3. This is a bug in PHP that
	// we had to fix, and we test for here regardless.
	it.each( [
		[ '2026-01-31', 'P1M', '2026-02-28' ],
		[ '2026-03-31', '-P1M', '2026-02-28' ],
		[ '2024-02-29', 'P1Y', '2025-02-28' ],
		[ '2026-01-31', 'P1M15D', '2026-03-15' ],
		[ '2026-01-31', 'P1M2W3D', '2026-03-17' ],
	] )(
		'clamps %s + %s to the end of the target month, giving %s',
		( today, constraint, expected ) => {
			onDate( today );

			expect( resolveDateConstraint( constraint ) ).toBe( expected );
		}
	);

	it.each( [
		[ '2026-12-31', '2026-01-01' ],
		[ 'P2M', 'P1M' ],
		[ 'P0D', '2026-08-25' ],
		[ '2026-08-27', 'P0D' ],
	] )( 'drops both limits for an inverted range %s to %s', ( min, max ) => {
		onDate( '2026-08-26' );

		expect( resolveDateConstraints( { min, max } ) ).toEqual( {
			min: undefined,
			max: undefined,
		} );
	} );

	it.each( [
		[ 'P0D', 'P0D', '2026-08-26', '2026-08-26' ],
		[ 'P0D', 'P1D', '2026-08-26', '2026-08-27' ],
		[ undefined, 'P0D', undefined, '2026-08-26' ],
		[ 'P0D', undefined, '2026-08-26', undefined ],
	] )(
		'keeps valid limits %s to %s',
		( min, max, expectedMin, expectedMax ) => {
			onDate( '2026-08-26' );

			expect( resolveDateConstraints( { min, max } ) ).toEqual( {
				min: expectedMin,
				max: expectedMax,
			} );
		}
	);

	it( 'drops a mixed range when the minimum moves past the maximum', () => {
		const field = { min: 'P0D', max: '2026-08-26' };
		onDate( '2026-08-26' );
		expect( resolveDateConstraints( field ) ).toEqual( {
			min: '2026-08-26',
			max: '2026-08-26',
		} );

		onDate( '2026-08-27' );
		expect( resolveDateConstraints( field ) ).toEqual( {
			min: undefined,
			max: undefined,
		} );
	} );

	it( 'follows the clock rather than the moment the page was rendered', () => {
		onDate( '2026-08-26' );
		expect( resolveDateConstraint( 'P0D' ) ).toBe( '2026-08-26' );

		jest.setSystemTime( new Date( '2026-08-27T12:00:00Z' ) );
		expect( resolveDateConstraint( 'P0D' ) ).toBe( '2026-08-27' );
	} );
} );
