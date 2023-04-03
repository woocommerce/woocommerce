/**
 * External dependencies
 */
import { program } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

dotenv.config();

program
	.name( 'code-freeze' )
	.version( '0.0.1' )
	.command( 'paul', 'Seeeealock', { isDefault: true } );

program.parse( process.argv );
