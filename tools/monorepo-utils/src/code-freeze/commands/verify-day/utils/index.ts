const MILLIS_IN_A_DAY = 24 * 60 * 60 * 1000;
export const DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE = 22;

/**
 * Get a Date object of now or the override time when specified.
 *
 * @param {string} now The time to use in checking if today is the day of the code freeze. Default to now.
 * @return {Date} The Date object of now or the override time when specified.
 */
export const getToday = ( now = 'now' ): Date => {
	const today = now === 'now' ? new Date() : new Date( now );
	if ( isNaN( today.getTime() ) ) {
		throw new Error(
			'Invalid date: Check the override parameter (-o, --override) is a correct Date string'
		);
	}
	return today;
};

/**
 * Get a future date from today to see if its the release day.
 *
 * @param {string} today The time to use in checking if today is the day of the code freeze. Default to now.
 * @return {Date} The Date object of the future date.
 */
export const getFutureDate = ( today: Date ) => {
	return new Date(
		today.getTime() + DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE * MILLIS_IN_A_DAY
	);
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
	const month = futureDate.getUTCMonth();
	const year = futureDate.getUTCFullYear();
	const firstDayOfMonth = new Date( Date.UTC( year, month, 1 ) );
	const dayOfWeek = firstDayOfMonth.getUTCDay();
	const secondTuesday = dayOfWeek <= 2 ? 10 - dayOfWeek : 17 - dayOfWeek;
	return futureDate.getUTCDate() === secondTuesday;
};
