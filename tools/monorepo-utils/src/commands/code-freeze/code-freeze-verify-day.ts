/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDay } from '../../lib/code-freeze';

const program = new Command()
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now')."
	)
	.action( () => {
		console.log( verifyDay() );

		process.exit( 0 );
	} );

program.parse( process.argv );
