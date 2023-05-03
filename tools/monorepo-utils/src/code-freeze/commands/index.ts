/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { verifyDayCommand } from './verify-day';

const program = new Command( 'code-freeze' )
	.description( 'Code freeze utilities' )
	.addCommand( verifyDayCommand );

export default program;
