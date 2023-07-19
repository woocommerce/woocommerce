/**
 * External dependencies
 */
import { DateTime } from 'luxon';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';
import { getToday } from '../../verify-day/utils/index';

/**
 * Get the second Tuesday of the month, given a DateTime.
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

/**
 * Get the version for a-releases given a particular freeze date.
 * Note: days later in the week will return the value as-if it were the freeze date.
 *
 * @param {string} override Time override (default: 'now').
 *
 * @return {string} The version for the given a-release.
 */
export const getVersion = ( override: string = 'now' ): string => {
	// July 12, 2023 is the start-point for 8.0.0-a.1, all versions follow that starting point.
	const startTime = DateTime.fromObject( {
		year: 2023,
		month: 7,
		day: 12,
		hour: 0,
		minute: 0,
	}, { zone: 'UTC' } );
	const today = getToday( override );
	const currentMonthFreeze = getSecondTuesday( today );
	const nextMonthFreeze = getSecondTuesday( today.plus( { months: 1 } ) );
	const upcomingFreeze = today < currentMonthFreeze ? currentMonthFreeze : nextMonthFreeze;
	const previousFreeze = getSecondTuesday( upcomingFreeze.minus( { days: 21 } ) );
	const monthNumber = ( previousFreeze.get( 'year' ) - startTime.get( 'year' ) ) * 12 + previousFreeze.get( 'month' ) - startTime.get( 'month' );
	const aVersion = Math.round( today.diff( previousFreeze, 'weeks' ).toObject().weeks ) + 1;
	const version = ( ( 80 + monthNumber ) / 10 ).toFixed( 1 ) + '.0-a.' + aVersion;
	return version;
};

/**
 * Get the version for monthly releases given a particular freeze date.
 * Note: days later in the week will return the value as-if it were the freeze date.
 *
 * @param {string} override Time override (default: 'now').
 *
 * @return {string} The version for the given monthly release.
 */
export const getVersionMonthly = ( override: string = 'now' ): string => {
	const today = getToday( override );
	const twoWeeksAhead = today.plus( { days: 14 } );
	const parts = getVersion( twoWeeksAhead.toISODate() ).split( '-' );
	return parts[0];
};
