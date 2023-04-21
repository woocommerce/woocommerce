/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { massEntryCommand } from './mass-entry';

const program = new Command( 'mass-changelog' )
	.description( 'Mass changelog entry utilities' )
	.addCommand( massEntryCommand, { isDefault: true } );

export default program;
