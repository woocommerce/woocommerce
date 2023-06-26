/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import List from './list';

const program = new Command( 'workflows' )
	.description( 'Profile Github workflows' )
	.addCommand( List );

export default program;
