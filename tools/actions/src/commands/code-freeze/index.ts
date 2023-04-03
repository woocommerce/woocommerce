/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

dotenv.config();

program
	.name( 'code-freeze' )
	.version( '0.0.1' )
	.command( 'verify-day', 'Verify that today is the day of the code freeze' );

program.parse( process.argv );
