/**
 * External dependencies
 */
import { DateTime } from 'luxon';

export const DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE = 22;

/**
 * Get a Date object of now or the override time when specified.
 *
 * @param {string} now The time to use in checking if today is the day of the code freeze. Default to now. Supports ISO formatted dates or 'now'.
 * @return {DateTime} The DateTime object of now or the override time when specified.
 */
export const getToday = ( now = 'now' ): DateTime => {
	const today = now === 'now' ? DateTime.now() : DateTime.fromISO( now, { zone: 'utc' } );
	if ( isNaN( today.toMillis() ) ) {
		throw new Error(
			'Invalid date: Check the override parameter (-o, --override) is a correct ISO formatted string or "now"'
		);
	}
	return today;
};

/**
 * Get a future date from today to see if its the release day.
 *
 * @param {DateTime} today The time to use in checking if today is the day of the code freeze.
 * @return {DateTime} The Date object of the future date.
 */
export const getFutureDate = ( today: DateTime ) => {
	return today.plus( { days: DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE } );
};
/**
 * Determines if today is the day of the code freeze.
 *
 * @param {string} now The time to use in checking if today is the day of the code freeze. Default to now.
 * @return {boolean} true if today is the day of the code freeze.
 */
export const isTodayCodeFreezeDay = ( now: string ) => {
	const today = getToday( now );
	const futureDate = getFutureDate( today );
	const month = futureDate.get( 'month' );
	const year = futureDate.get( 'year' );
	const firstDayOfMonth = DateTime.utc( year, month, 1 );
	const dayOfWeek = firstDayOfMonth.get( 'weekday' );
	const secondTuesday = dayOfWeek <= 2 ? 10 - dayOfWeek : 17 - dayOfWeek;
	return futureDate.get( 'day' ) === secondTuesday;
};
