/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDayCommand } from './verify-day';
import { getVersionCommand } from './get-version';
import { milestoneCommand } from './milestone';
import { branchCommand } from './branch';
import { versionBumpCommand } from './version-bump';
import { changelogCommand } from './changelog';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand )
	.addCommand( getVersionCommand )
	.addCommand( milestoneCommand )
	.addCommand( branchCommand )
	.addCommand( versionBumpCommand )
	.addCommand( changelogCommand );

export default program;
