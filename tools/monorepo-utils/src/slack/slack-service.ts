/**
 * External dependencies
 */
import { setOutput } from '@actions/core';
import { WebClient, ErrorCode } from '@slack/web-api';
import { basename } from 'path';
import { existsSync } from 'fs';

/**
 * Internal dependencies
 */
import { isGithubCI } from '../core/environment';
import { Logger } from '../core/logger';

// Resolves channel IDs from the SLACK_CHANNELS env variable (comma-separated).
// Throws if not set or empty.
export function resolveChannels(): string[] {
	const value = process.env.SLACK_CHANNELS;
	const errorMessage =
		'SLACK_CHANNELS environment variable must be set with comma-separated channel IDs.';

	if ( ! value ) {
		Logger.error( errorMessage );
		return null;
	}

	const channels = value
		.split( ',' )
		.map( ( v ) => v.trim() )
		.filter( Boolean );

	if ( channels.length === 0 ) {
		Logger.error( errorMessage );
		return null;
	}

	return channels;
}

export async function sendMessage(
	client: WebClient,
	text: string,
	channels: string[]
) {
	if ( ! text ) {
		Logger.error( 'The text argument is missing.' );
	}
	Logger.startTask(
		`Attempting to send message to ${ channels.length } channels`
	);
	for ( const channel of channels ) {
		try {
			const response = await client.chat.postMessage( {
				channel,
				text: text.replace( /\\n/g, '\n' ),
			} );
			if ( ! response.ok ) {
				Logger.error(
					`Slack client returned an error: ${ response.error }, message failed to send.`
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
	Logger.endTask();
}

export async function sendFile(
	client: WebClient,
	text: string,
	filePath: string,
	channels: string[],
	replyTs: string
) {
	if ( ! existsSync( filePath ) ) {
		Logger.error( `Unable to open file with path: ${ filePath }` );
	}
	Logger.startTask(
		`Attempting to upload file to ${ channels.length } channels`
	);
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
				thread_ts: replyTs ? replyTs : null,
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
				Logger.error( e.message );
			}
		}
	}
	Logger.endTask();
}
