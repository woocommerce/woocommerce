/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDay } from '../utils/index';

export const verifyDayCommand = new Command( 'verify-day' )
	.description( 'Verify if today is the code freeze day' )
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now')."
	)
	.action( () => {
		console.log( verifyDay() );

		process.exit( 0 );
	} );
