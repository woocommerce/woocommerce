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

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand )
	.addCommand( milestoneCommand )
	.addCommand( branchCommand );

export default program;
