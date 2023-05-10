/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDayCommand } from './verify-day';
import { milestoneCommand } from './milestone';
import { branchCommand } from './branch';
import { versionBumpCommand } from './version-bump';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand )
	.addCommand( milestoneCommand )
	.addCommand( branchCommand )
	.addCommand( versionBumpCommand );

export default program;
