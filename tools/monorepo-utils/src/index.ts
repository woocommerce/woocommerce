/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import CodeFreeze from './code-freeze/commands';
import MassChangelog from './mass-changelog/commands';

const program = new Command()
	.name( 'utils' )
	.description( 'Monorepo utilities' )
	.addCommand( CodeFreeze )
	.addCommand( MassChangelog );

program.parse( process.argv );
