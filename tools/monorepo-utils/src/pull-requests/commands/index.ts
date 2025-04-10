/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { addChangelogFileCommand } from './add-changelog-file';

/**
 * Commands
 */
const program = new Command( 'pull-request' )
	.description( 'GitHub pull request related utilities' )
	.addCommand( addChangelogFileCommand );

export default program;
