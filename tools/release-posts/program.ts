/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

export const program = new Command();

program
	.name( 'post' )
	.description( 'CLI to automate generation of posts for beta and release.' )
	.version( '0.0.1' );
