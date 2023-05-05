/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */

const program = new Command( 'slack' ).description(
	'Slack message sending utilities'
);

export default program;
