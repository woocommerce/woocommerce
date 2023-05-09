/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { requestAsync } from '../../../core/util';

type SlackResponse = {
	ok: boolean;
	error?: string;
};

export const slackMessageCommand = new Command( 'message' )
	.description( 'Send a plain-text message to a slack channel' )
	.argument(
		'<token>',
		'Slack authentication token bearing required scopes.'
	)
	.argument( '<channel>', 'Slack channel to send the message to.' )
	.argument( '<text>', 'Text based message to send to the slack channel.' )
	.option(
		'--dontfail',
		'Do not fail the command if the message fails to send.'
	)
	.action( async ( token, channel, text, { dontfail } ) => {
		Logger.startTask( 'Attempting to send message to slack' );

		const shouldFail = ! dontfail;

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
			const { statusCode, body } = await requestAsync(
				options,
				JSON.stringify( { channel, text } )
			);

			Logger.endTask();

			const response = JSON.parse( body ) as SlackResponse;

			if ( ! response.ok || statusCode !== 200 ) {
				Logger.error(
					`Slack API returned an error: ${ response?.error }, message failed to send.`,
					shouldFail
				);
			} else {
				Logger.notice( 'Slack message sent successfully' );
			}
		} catch ( e: unknown ) {
			Logger.error( e, shouldFail );
		}
	} );
