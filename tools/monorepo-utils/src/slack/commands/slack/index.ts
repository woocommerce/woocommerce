/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { postToSlack } from './slack-service';

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
