/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';
import { WebClient, ErrorCode } from '@slack/web-api';
import { basename } from 'path';
import { existsSync } from 'fs';

/**
 * Internal dependencies
 */
import { getEnvVar, isGithubCI } from '../../../core/environment';
import { resolveChannels } from './utils';
import { Logger } from '../../../core/logger';

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
		const token = getEnvVar( 'SLACK_TOKEN', true );
		const channels = resolveChannels();

		if ( options.file ) {
			// File upload mode
			const filePath = options.file;
			if ( ! existsSync( filePath ) ) {
				Logger.error( `Unable to open file with path: ${ filePath }` );
			}
			Logger.startTask(
				`Attempting to upload file to ${ channels.length } channels`
			);
			const client = new WebClient( token );
			for ( const channel of channels ) {
				try {
					const requestOptions = {
						file: filePath,
						filename: basename( filePath ),
						channel_id: channel,
						initial_comment: text
							? text.replace( /\\n/g, '\n' )
							: undefined,
						request_file_info: false,
						thread_ts: options.replyTs ? options.replyTs : null,
					};
					await client.files.uploadV2( requestOptions );
					Logger.notice( `Successfully uploaded ${ filePath }` );
				} catch ( e ) {
					if (
						'code' in e &&
						e.code === ErrorCode.PlatformError &&
						'message' in e &&
						e.message.includes( 'missing_scope' )
					) {
						Logger.error(
							'The provided token does not have the required scopes, please add files:write and chat:write to the token.'
						);
					} else {
						Logger.error( e );
					}
				}
			}
			Logger.endTask();
		} else {
			// Message mode
			if ( ! text ) {
				Logger.error( 'The text argument is missing.' );
			}
			Logger.startTask(
				`Attempting to send message to ${ channels.length } channels`
			);
			const client = new WebClient( token );
			for ( const channel of channels ) {
				try {
					const response = await client.chat.postMessage({
						channel,
						text: text.replace( /\\n/g, '\n' ),
					});
					Logger.endTask();
					if ( ! response.ok ) {
						Logger.error(
							`Slack API returned an error: ${ response.error }, message failed to send.`
						);
					} else {
						Logger.notice( `Slack message sent successfully` );
						if ( isGithubCI() ) {
							setOutput( 'ts', response.ts );
						}
					}
				} catch ( e ) {
					Logger.error( e );
				}
			}
		}
	} );

export default program;
