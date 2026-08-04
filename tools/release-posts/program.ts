/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

export const program = new Command();

program
	.name( 'post' )
	.description( 'CLI to automate generation of release posts.' )
	.version( '0.0.1' );
