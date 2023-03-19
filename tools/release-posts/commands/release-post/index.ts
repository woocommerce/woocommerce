/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

dotenv.config();

program
	.name( 'release-post' )
	.version( '0.0.1' )
	.command( 'release', 'Generate release post', { isDefault: true } )
	.command( 'beta', 'Generate draft beta release post' )
	.command(
		'contributors',
		'Generate a list of contributors for a release post'
	);

program.parse( process.argv );
