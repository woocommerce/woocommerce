/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import List from './list';
import Profile from './profile';

const program = new Command( 'workflows' )
	.description( 'Profile Github workflows' )
	.addCommand( Profile )
	.addCommand( List );

export default program;
