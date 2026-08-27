/**
 * Internal dependencies
 */
import { resolveDateConstraint } from '../date-constraints';

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
		[ 'P3M', '2026-11-26' ],
		[ '-P18Y', '2008-08-26' ],
		[ 'P1Y2M3D', '2027-10-29' ],
	] )( 'resolves %s to %s', ( constraint, expected ) => {
		onDate( '2026-08-26' );

		expect( resolveDateConstraint( constraint ) ).toBe( expected );
	} );

	// PHP's own DateInterval arithmetic would roll these forward into the next month. DateFieldType
	// reimplements Temporal's clamp so the server agrees with what the picker offers here.
	it.each( [
		[ '2026-01-31', 'P1M', '2026-02-28' ],
		[ '2026-03-31', '-P1M', '2026-02-28' ],
		[ '2024-02-29', 'P1Y', '2025-02-28' ],
		[ '2026-01-31', 'P1M15D', '2026-03-15' ],
	] )(
		'clamps %s + %s to the end of the target month, giving %s',
		( today, constraint, expected ) => {
			onDate( today );

			expect( resolveDateConstraint( constraint ) ).toBe( expected );
		}
	);

	it( 'follows the clock rather than the moment the page was rendered', () => {
		onDate( '2026-08-26' );
		expect( resolveDateConstraint( 'P0D' ) ).toBe( '2026-08-26' );

		jest.setSystemTime( new Date( '2026-08-27T12:00:00Z' ) );
		expect( resolveDateConstraint( 'P0D' ) ).toBe( '2026-08-27' );
	} );
} );
