/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';

program
	.name( 'analyzer' )
	.version( '0.0.1' )
	.command( 'lint', 'Lint changes', { isDefault: true } )
	.command( 'major-minor', 'Determine major/minor version of a plugin' );

program.parse( process.argv );
