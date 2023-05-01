/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import {
	isTodayCodeFreezeDay,
	DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
	getToday,
	getFutureDate,
} from './utils';
import { Logger } from '../../../core/logger';
import { getEnvVar } from '../../../core/environment';

export const verifyDayCommand = new Command( 'verify-day' )
	.description( 'Verify if today is the code freeze day' )
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now')."
	)
	.action( ( { override } ) => {
		const today = getToday( override );
		const futureDate = getFutureDate( today );
		Logger.warn( "Today's timestamp UTC is: " + today.toUTCString() );
		Logger.warn(
			`Checking to see if ${ DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE } days from today is the second Tuesday of the month.`
		);
		const isCodeFreezeDay = isTodayCodeFreezeDay( override );
		Logger.notice(
			`${ futureDate.toUTCString() } ${
				isCodeFreezeDay ? 'is' : 'is not'
			} release day.`
		);
		Logger.notice(
			`Today is ${ isCodeFreezeDay ? 'indeed' : 'not' } code freeze day.`
		);

		if ( getEnvVar( 'CI' ) ) {
			setOutput( 'freeze', isCodeFreezeDay.toString() );
		}

		process.exit( 0 );
	} );
