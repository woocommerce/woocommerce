/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';
import { existsSync } from 'fs';

if ( existsSync( '.env' ) ) {
	process.loadEnvFile();
}

program
	.name( 'analyzer' )
	.version( '0.0.1' )
	.command( 'lint', 'Lint changes', { isDefault: true } )
	.command( 'major-minor', 'Determine major/minor version of a plugin' )
	.command( 'scan', 'Detect changes between plugin versions' );

program.parse( process.argv );
