/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from 'cli-core/src/logger';
import { execSync } from 'child_process';

export const massEntryCommand = new Command( 'mass-entries' )
	.description(
		'Simplify mass entry of changelogs for a large set of changes'
	)
	.action( () => {
		Logger.startTask( 'Detecting packages that require a changelog entry' );

		const command = 'pnpm m ls --json --depth=-1';

		try {
			const output = execSync( command, { encoding: 'utf8' } );
			const result = JSON.parse( output );
			console.log( result ); // The JavaScript object
		} catch ( error ) {
			console.error( error );
		}

		Logger.endTask();
	} );
