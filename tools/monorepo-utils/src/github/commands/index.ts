/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { replaceLabelsCommand } from './replace-labels';

const program = new Command( 'github' )
	.description( 'Github utilities' )
	.addCommand( replaceLabelsCommand );

export default program;
