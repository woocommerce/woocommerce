/**
 * External dependencies
 */
import { DateTime } from 'luxon';

export const DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE = 19;

/**
 * Get a DateTime object of now or the override time when specified. DateTime is normalized to start of day.
 *
 * @param {string} now The time to use in checking if today is the day of the code freeze. Default to now. Supports ISO formatted dates or 'now'.
 *
 * @return {DateTime} The DateTime object of now or the override time when specified.
 */
export const getToday = ( now = 'now' ): DateTime => {
	const today =
		now === 'now'
			? DateTime.now().setZone( 'utc' )
			: DateTime.fromISO( now, { zone: 'utc' } );
	if ( isNaN( today.toMillis() ) ) {
		throw new Error(
			'Invalid date: Check the override parameter (-o, --override) is a correct ISO formatted string or "now"'
		);
	}
	return today.set( { hour: 0, minute: 0, second: 0, millisecond: 0 } );
};

/**
 * Get the second Tuesday of the month, given a DateTime.
 *
 * @param {DateTime} when A DateTime object.
 *
 * @return {DateTime} The second Tuesday of the month contained in the input.
 */
export const getSecondTuesday = ( when: DateTime ): DateTime => {
	const year = when.get( 'year' );
	const month = when.get( 'month' );
	const firstDayOfMonth = DateTime.utc( year, month, 1 );
	const dayOfWeek = firstDayOfMonth.get( 'weekday' );
	const secondTuesday = dayOfWeek <= 2 ? 10 - dayOfWeek : 17 - dayOfWeek;
	return DateTime.utc( year, month, secondTuesday );
};

export const getMonthlyCycle = ( when: DateTime, development = true ) => {
	// July 12, 2023 is the start-point for 8.0.0, all versions follow that starting point.
	const startTime = DateTime.fromObject(
		{
			year: 2023,
			month: 7,
			day: 12,
			hour: 0,
			minute: 0,
		},
		{ zone: 'UTC' }
	);
	const currentMonthRelease = getSecondTuesday( when );
	const nextMonthRelease = getSecondTuesday(
		currentMonthRelease.plus( { months: 1 } )
	);
	const release =
		when <= currentMonthRelease ? currentMonthRelease : nextMonthRelease;
	const previousRelease = getSecondTuesday(
		release.minus( { days: DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE + 2 } )
	);
	const nextRelease = getSecondTuesday( release.plus( { months: 1 } ) );
	const freeze = release.minus( {
		days: DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE + 1,
	} );
	const monthNumber =
		( previousRelease.get( 'year' ) - startTime.get( 'year' ) ) * 12 +
		previousRelease.get( 'month' ) -
		startTime.get( 'month' );
	const version = ( ( 80 + monthNumber ) / 10 ).toFixed( 1 ) + '.0';

	if ( development ) {
		if ( when > freeze ) {
			return getMonthlyCycle( nextRelease, false );
		}
	}

	const begin = previousRelease.minus( {
		days: DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
	} );

	return {
		version,
		begin,
		freeze,
		release,
	};
};

/**
 * Get version and all dates / related to an accelerated cycle.
 *
 * @param {DateTime} when        A DateTime object.
 * @param {boolean}  development When true, the active development cycle will be returned, otherwise the active release cycle.
 * @return {Object} An object containing version and dates for a release.
 */
export const getAcceleratedCycle = ( when: DateTime, development = true ) => {
	if ( ! development ) {
		when = when.minus( { week: 1 } );
	}
	const dayOfWeek = when.get( 'weekday' );
	const daysTilWednesday = dayOfWeek < 4 ? 3 - dayOfWeek : 10 - dayOfWeek;
	const freeze = when.plus( { days: daysTilWednesday } );
	const lastAccelerated = freeze.minus( { days: 1 } );
	const release = freeze.plus( { days: 6 } );
	const begin = freeze.minus( { days: 6 } );
	const currentMonthRelease = getSecondTuesday( lastAccelerated );
	const nextMonthRelease = getSecondTuesday(
		currentMonthRelease.plus( { months: 1 } )
	);
	const monthlyRelease =
		freeze <= currentMonthRelease ? currentMonthRelease : nextMonthRelease;
	const monthlyCycle = getMonthlyCycle( monthlyRelease, false );
	const previousMonthlyRelease = getSecondTuesday(
		monthlyRelease.minus( { days: 28 } )
	);

	const aVersion =
		10 *
		( lastAccelerated.diff( previousMonthlyRelease, 'weeks' ).toObject()
			.weeks +
			1 );
	const version = `${ monthlyCycle.version }.${ aVersion }`;

	return {
		version,
		begin,
		freeze,
		release,
	};
};

export const getVersionsBetween = ( start: DateTime, end: DateTime ) => {
	if ( start > end ) {
		return getVersionsBetween( end, start );
	}
	const versions = {};
	for ( let i = start; i < end; i = i.plus( { days: 28 } ) ) {
		const monthly = getMonthlyCycle( i, false );
		versions[ monthly.version ] = monthly;
	}
	for ( let i = start; i < end; i = i.plus( { days: 7 } ) ) {
		const accelerated = getAcceleratedCycle( i, false );
		versions[ accelerated.version ] = accelerated;
	}
	return Object.values( versions );
};
