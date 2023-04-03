/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

const program = new Command().command( 'paul' ).action( () => {
	console.log( 'hello world' );
} );

program.parse( process.argv );
