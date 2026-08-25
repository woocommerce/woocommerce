/**
 * Internal dependencies
 */
import { deriveSummary } from '../use-notices-data';

/**
 * Pin the system clock to a fixed UTC instant so `deriveSummary`'s
 * `new Date()` reads are deterministic across test runs.
 *
 * @param {string} isoInstant An ISO 8601 UTC instant, e.g. '2024-06-15T12:00:00Z'.
 */
const pinNow = ( isoInstant ) => {
	jest.useFakeTimers().setSystemTime( new Date( isoInstant ) );
};

describe( 'deriveSummary', () => {
	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'computes today and the trailing 30-day window from timeseries rows', () => {
		// Pinned mid-month, away from any month boundary, and mid-day UTC
		// to prove time-of-day doesn't affect the UTC-day comparison.
		pinNow( '2024-06-15T12:34:56Z' );

		const rows = [
			{ date: '2024-06-15', signups: 3, notifications_sent: 2 },
			{ date: '2024-06-10', signups: 5, notifications_sent: 4 },
			{ date: '2024-06-01', signups: 1, notifications_sent: 1 },
		];

		const summary = deriveSummary( rows );

		expect( summary ).toEqual( {
			totals: {
				today: { notifications_sent: 2, total_signups: 3 },
				this_month: { notifications_sent: 7, total_signups: 9 },
			},
		} );
	} );

	it( 'maps the API `signups` field to `total_signups` in the output', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const rows = [
			{ date: '2024-06-15', signups: 42, notifications_sent: 0 },
		];

		const summary = deriveSummary( rows );

		expect( summary.totals.today.total_signups ).toBe( 42 );
		expect( summary.totals.today ).not.toHaveProperty( 'signups' );
	} );

	it( 'returns zeros for today when no row matches the current UTC date', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const rows = [
			{ date: '2024-06-14', signups: 10, notifications_sent: 8 },
		];

		const summary = deriveSummary( rows );

		expect( summary.totals.today ).toEqual( {
			notifications_sent: 0,
			total_signups: 0,
		} );
	} );

	it( 'returns all zeros for an empty rows array', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const summary = deriveSummary( [] );

		expect( summary ).toEqual( {
			totals: {
				today: { notifications_sent: 0, total_signups: 0 },
				this_month: { notifications_sent: 0, total_signups: 0 },
			},
		} );
	} );

	it( 'includes a row exactly 29 days back and excludes one exactly 30 days back', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const rows = [
			// today - 29 days
			{ date: '2024-05-17', signups: 100, notifications_sent: 200 },
			// today - 30 days
			{ date: '2024-05-16', signups: 1000, notifications_sent: 2000 },
		];

		const summary = deriveSummary( rows );

		expect( summary.totals.this_month ).toEqual( {
			notifications_sent: 200,
			total_signups: 100,
		} );
	} );

	it( 'excludes rows dated in the future or older than the trailing window', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const rows = [
			{ date: '2024-06-16', signups: 50, notifications_sent: 50 }, // future
			{ date: '2024-01-01', signups: 999, notifications_sent: 999 }, // too old
		];

		const summary = deriveSummary( rows );

		expect( summary.totals.this_month ).toEqual( {
			notifications_sent: 0,
			total_signups: 0,
		} );
	} );

	it( 'coerces missing or garbage field values to 0 instead of NaN', () => {
		pinNow( '2024-06-15T00:00:00Z' );

		const rows = [
			{
				date: '2024-06-15',
				signups: undefined,
				notifications_sent: null,
			},
			{
				date: '2024-06-10',
				signups: null,
				notifications_sent: undefined,
			},
		];

		const summary = deriveSummary( rows );

		expect( summary ).toEqual( {
			totals: {
				today: { notifications_sent: 0, total_signups: 0 },
				this_month: { notifications_sent: 0, total_signups: 0 },
			},
		} );
	} );

	it( 'sums a trailing 30-day window that spans a calendar month boundary', () => {
		// Trailing window from a pin near month start reaches back into the
		// previous calendar month, unlike a "this calendar month" total.
		pinNow( '2024-06-05T00:00:00Z' );

		const rows = [
			{ date: '2024-06-05', signups: 2, notifications_sent: 1 }, // today
			{ date: '2024-05-20', signups: 3, notifications_sent: 2 }, // previous month, in window
			{ date: '2024-05-07', signups: 4, notifications_sent: 3 }, // today - 29 days, in window
			{ date: '2024-05-06', signups: 999, notifications_sent: 999 }, // today - 30 days, excluded
		];

		const summary = deriveSummary( rows );

		expect( summary.totals.this_month ).toEqual( {
			notifications_sent: 6,
			total_signups: 9,
		} );
	} );
} );
