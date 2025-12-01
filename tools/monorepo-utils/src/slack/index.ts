/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { WebClient } from '@slack/web-api';

/**
 * Internal dependencies
 */
import { getEnvVar } from '../core/environment';
import { resolveChannels, sendFile, sendMessage } from './slack-service';

export async function postToSlack( text: string, options ) {
	const token = getEnvVar( 'SLACK_TOKEN', true );
	const channels = resolveChannels();
	const client = new WebClient( token );

	if ( options.file ) {
		// File upload mode
		await sendFile( client, text, options.file, channels, options.replyTs );
	} else {
		// Message mode
		await sendMessage( client, text, channels, options.replyTs );
	}
}

const program = new Command( 'slack' )
	.description( 'Slack message sending utilities' )
	.argument(
		'<text>',
		'Text message to send or comment to attach to the file upload.'
	)
	.option(
		'--file <filePath>',
		'File path to upload to the slack channel (if uploading a file).'
	)
	.option(
		'--reply-ts <replyTs>',
		'Reply to the message with the corresponding ts (file upload only).'
	)
	.action( async ( text, options ) => {
		await postToSlack( text, options as any );
	} );

export default program;
