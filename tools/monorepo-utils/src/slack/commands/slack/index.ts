/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { slackMessageCommand } from './slack-message';
import { slackFileCommand } from './slack-file';

/**
 * Internal dependencies
 */

const program = new Command( 'slack' )
	.description( 'Slack message sending utilities' )
	.addCommand( slackMessageCommand, { isDefault: true } )
	.addCommand( slackFileCommand );

export default program;
