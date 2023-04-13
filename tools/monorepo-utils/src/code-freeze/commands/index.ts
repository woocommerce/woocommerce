/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDayCommand } from './verify-day';
import { milesStoneCommand } from './milestone';
import { branchCommand } from './branch';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand )
	.addCommand( milesStoneCommand )
	.addCommand( branchCommand );

export default program;
