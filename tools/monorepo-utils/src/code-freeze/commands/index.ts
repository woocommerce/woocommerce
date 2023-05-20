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
import { changelogCommand } from './changelog';
import { versionPlaceholderCommand } from './version-placeholder';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand )
	.addCommand( milestoneCommand )
	.addCommand( branchCommand )
	.addCommand( versionBumpCommand )
	.addCommand( changelogCommand )
	.addCommand( versionPlaceholderCommand );

export default program;
