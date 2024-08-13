/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { labelsCommand } from './labels';

const program = new Command( 'github' )
	.description( 'Github utilities' )
	.addCommand( labelsCommand );

export default program;
