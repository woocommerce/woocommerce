/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { requestAsync } from '../../../core/util';
import { isGithubCI } from '../../../core/environment';

type SlackResponse = {
	ok: boolean;
	error?: string;
	channel?: string;
	ts?: string;
};

export const slackMessageCommand = new Command( 'message' )
	.description( 'Send a plain-text message to a slack channel' )
	.argument(
		'<token>',
		'Slack authentication token bearing required scopes.'
	)
	.argument( '<text>', 'Text based message to send to the slack channel.' )
	.argument(
		'<channels...>',
		'Slack channels to send the message to. Pass as many as you like.'
	)
	.option(
		'--dont-fail',
		'Do not fail the command if a message fails to send to any channel.'
	)
	.action( async ( token, text, channels, { dontFail } ) => {
		Logger.startTask(
			`Attempting to send message to Slack for channels: ${ channels.join(
				','
			) }`
		);

		const shouldFail = ! dontFail;

		for ( const channel of channels ) {
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
					JSON.stringify( {
						channel,
						text: text.replace( /\\n/g, '\n' ),
					} )
				);

				Logger.endTask();

				const response = JSON.parse( body ) as SlackResponse;

				if ( ! response.ok || statusCode !== 200 ) {
					Logger.error(
						`Slack API returned an error: ${ response?.error }, message failed to send to ${ channel }.`,
						shouldFail
					);
				} else {
					Logger.notice(
						`Slack message sent successfully to channel: ${ channel }`
					);
					if ( isGithubCI() ) {
						setOutput( 'ts', response.ts );
					}
				}
			} catch ( e: unknown ) {
				Logger.error( e, shouldFail );
			}
		}
	} );
