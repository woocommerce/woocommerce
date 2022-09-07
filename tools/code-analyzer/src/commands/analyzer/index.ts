/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';

program
	.name( 'analyzer' )
	.version( '0.0.1' )
	.command( 'lint', 'Lint changes', { isDefault: true } );

program.parse( process.argv );
