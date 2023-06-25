/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { slackMessageCommand } from './slack-message';

/**
 * Internal dependencies
 */

const program = new Command( 'slack' )
	.description( 'Slack message sending utilities' )
	.addCommand( slackMessageCommand, { isDefault: true } );

export default program;
