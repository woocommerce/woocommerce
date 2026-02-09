export const getFirstTuesdayOfTheMonth = ( month: number ): Date => {
	// create a new Date object for the first day of the month
	const firstDayOfMonth = new Date( new Date().getFullYear(), month, 1 );

	// create a new Date object for the first Tuesday of the month
	const firstTuesday = new Date( firstDayOfMonth );

	firstTuesday.setDate( 1 + ( ( 2 - firstDayOfMonth.getDay() + 7 ) % 7 ) );

	return firstTuesday;
};

export const getSecondTuesdayOfTheMonth = ( month: number ): Date => {
	// create a new Date object for the first Tuesday of the month
	const firstTuesday = getFirstTuesdayOfTheMonth( month );

	// create a new Date object for the second Tuesday of the current month
	const secondTuesday = new Date( firstTuesday );
	secondTuesday.setDate( secondTuesday.getDate() + 7 );

	return secondTuesday;
};
