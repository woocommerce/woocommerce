/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import chalk from 'chalk';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import {
	isTodayCodeFreezeDay,
	DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
	getToday,
	getFutureDate,
} from '../utils/index';

export const verifyDayCommand = new Command( 'verify-day' )
	.description( 'Verify if today is the code freeze day' )
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now')."
	)
	.option(
		'-g --github',
		'CLI command is used in the Github Actions context.'
	)
	.action( ( { override, github } ) => {
		const today = getToday( override );
		const futureDate = getFutureDate( today );
		console.log(
			chalk.yellow(
				"Today's timestamp UTC is: " + today.toUTCString() + '\n'
			)
		);
		console.log(
			chalk.yellow(
				`Checking to see if ${ DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE } days from today is the second Tuesday of the month.\n`
			)
		);
		const isCodeFreezeDay = isTodayCodeFreezeDay( override );
		console.log(
			chalk.green(
				`${ futureDate.toUTCString() } ${
					isCodeFreezeDay ? 'is' : 'is not'
				} release day.\n`
			)
		);
		console.log(
			chalk.green(
				`Today is ${
					isCodeFreezeDay ? 'indeed' : 'not'
				} code freeze day.`
			)
		);

		if ( github ) {
			setOutput( 'freeze', isCodeFreezeDay.toString() );
		}

		process.exit( 0 );
	} );
