const RELEASE_DATE_PATTERN = /^(\d{2})-(\d{2})-(\d{4})$/;

export const formatReleaseDate = ( date: Date ): string =>
	[
		String( date.getMonth() + 1 ).padStart( 2, '0' ),
		String( date.getDate() ).padStart( 2, '0' ),
		date.getFullYear(),
	].join( '-' );

export const parseReleaseDate = ( releaseDate: string ): Date => {
	const match = RELEASE_DATE_PATTERN.exec( releaseDate );

	if ( ! match ) {
		throw new Error(
			`Invalid release date: ${ releaseDate }. Provide release date as mm-dd-yyyy.`
		);
	}

	const month = Number( match[ 1 ] );
	const day = Number( match[ 2 ] );
	const year = Number( match[ 3 ] );
	const date = new Date( year, month - 1, day );

	if (
		date.getFullYear() !== year ||
		date.getMonth() !== month - 1 ||
		date.getDate() !== day
	) {
		throw new Error(
			`Invalid release date: ${ releaseDate }. Provide release date as mm-dd-yyyy.`
		);
	}

	return date;
};

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
