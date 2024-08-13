/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { getVersionCommand } from './get-version';
import { milestoneCommand } from './milestone';
import { branchCommand } from './branch';
import { versionBumpCommand } from './version-bump';
import { changelogCommand } from './changelog';
import { acceleratedPrepCommand } from './accelerated-prep';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( getVersionCommand )
	.addCommand( milestoneCommand )
	.addCommand( branchCommand )
	.addCommand( versionBumpCommand )
	.addCommand( changelogCommand )
	.addCommand( acceleratedPrepCommand );

export default program;
