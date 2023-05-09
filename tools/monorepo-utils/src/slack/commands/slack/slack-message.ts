/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { requestAsync } from '../../../core/util';

export const slackMessageCommand = new Command( 'message' )
	.description( 'Send a plain-text message to a slack channel' )
	.argument(
		'<token>',
		'Slack authentication token bearing required scopes.'
	)
	.argument( '<channel>', 'Slack channel to send the message to.' )
	.argument( '<text>', 'Text based message to send to the slack channel.' )
	.option( '--fail', 'Fail the command if the message fails to send.', true )
	.action( async ( token, channel, text, { fail } ) => {
		Logger.startTask( 'Attempting to send message to slack' );

		// Define the request options
		const options = {
			hostname: 'slack.com',
			path: '/api/chat.postMessage',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Authorization: `Bearer ${ token }`,
			},
		};

		try {
			const { statusCode } = await requestAsync(
				options,
				JSON.stringify( { channel, text } )
			);

			Logger.endTask();

			if ( statusCode !== 200 ) {
				Logger.error(
					`Slack API returned a non-200 response: ${ statusCode }, message failed to send.`,
					fail
				);
			} else {
				Logger.notice( 'Slack message sent successfully' );
			}
		} catch ( e: unknown ) {
			Logger.error( e, fail );
		}
	} );
