/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';
import { existsSync } from 'fs';
import { readFile } from 'fs/promises';
import { basename } from 'path';
import FormData from 'form-data';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { requestAsync } from '../../../core/util';
import { isGithubCI } from '../../../core/environment';

type SlackResponse = {
	ok: boolean;
	error?: string;
	ts: string;
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
	.option(
		'--reply-ts <ts>',
		'Message will be in reply to message specified by ts value.'
	)
	.option(
		'--attach-file <file>',
		'The specified file will be added as an attachment.'
	)
	.action( async ( token, text, channels, { dontFail, replyTs, attachFile } ) => {
		if ( attachFile && ! existsSync( attachFile ) ) {
			Logger.error( `Unable to open file ${ attachFile }` );
			return;
		}

		Logger.startTask(
			`Attempting to send message to Slack for channels: ${ channels.join(
				','
			) }`
		);

		const shouldFail = ! dontFail;

		for ( const channel of channels ) {
			const form = new FormData();
			form.append( 'channel', channel );

			if ( attachFile ) {
				const file = await readFile( attachFile );
				form.append( 'initial_comment', text.replace( /\\n/g, '\n' ) );
				form.append( 'file', file, basename( attachFile ) );
			} else {
				form.append( 'text', text.replace( /\\n/g, '\n' ) );
			}

			if ( replyTs ) {
				form.append( 'thread_ts', replyTs );
			}

			// Define the request options
			const options = {
				hostname: 'slack.com',
				path: attachFile ? '/api/files.upload' : '/api/chat.postMessage',
				method: 'POST',
				headers: {
					...form.getHeaders(),
					Authorization: `Bearer ${ token }`,
				},
			};

			try {
				const { statusCode, body } = await requestAsync(
					options,
					form.getBuffer()
				);

				Logger.endTask();

				const response = JSON.parse( body ) as SlackResponse;

				Logger.notice( body );

				if ( ! response.ok || statusCode !== 200 ) {
					Logger.error(
						`Slack API returned an error: ${ response?.error }, message failed to send to ${ channel }.`,
						shouldFail
					);
				} else {
					Logger.notice(
						`Slack message sent successfully to channel: ${ channel }. Message has ts: ${ response.ts }`
					);
					if ( isGithubCI() ) {
						setOutput( `ts_${ channel }`, response.ts );
					}
				}
			} catch ( e: unknown ) {
				Logger.error( e, shouldFail );
			}
		}
	} );
