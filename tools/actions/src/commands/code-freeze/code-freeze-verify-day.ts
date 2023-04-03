/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

const program = new Command()
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now')."
	)
	.action( ( options ) => {
		const { override } = options;
		const now = override ? new Date( override ) : new Date();

		// Code freeze comes 22 days prior to release day.
		const releaseDay = new Date( now );
		releaseDay.setDate( releaseDay.getDate() + 22 );

		const releaseDayOfWeek = releaseDay.getDay();
		const releaseDayOfMonth = releaseDay.getDate();

		const isCodeFreezeDay =
			releaseDayOfWeek === 2 &&
			releaseDayOfMonth >= 8 &&
			releaseDayOfMonth <= 14;

		console.log( isCodeFreezeDay );

		process.exit( 0 );
	} );

program.parse( process.argv );
