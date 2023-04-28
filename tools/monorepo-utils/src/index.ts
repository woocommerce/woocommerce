/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import figlet from 'figlet';
import chalk from 'chalk';

/**
 * Internal dependencies
 */
import CodeFreeze from './code-freeze/commands';

console.log(
	chalk.rgb( 150, 88, 138 ).bold( figlet.textSync( 'WooCommerce Utilities' ) )
);

export const program = new Command()
	.name( 'utils' )
	.description( 'Monorepo utilities' )
	.addCommand( CodeFreeze );
