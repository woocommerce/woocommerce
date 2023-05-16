/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { extractCommand } from './extract';

const program = new Command( 'changelog' )
	.description( 'Changelog utilities' )
	.addCommand( extractCommand );

export default program;
