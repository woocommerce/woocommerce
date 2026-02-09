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

/**
 * Resolves channel IDs from the SLACK_CHANNELS environment variable (comma-separated).
 * Logs an error and returns null if the variable is not set or contains no valid channels.
 *
 * @return {string[] | null} Array of channel IDs, or null if not set/invalid.
 */
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

/**
 * Sends a message to one or more Slack channels using the provided WebClient.
 * Logs errors for missing text, Slack API errors, or exceptions.
 *
 * @param {WebClient} client   - An instance of Slack WebClient.
 * @param {string}    text     - The message text to send.
 * @param {string[]}  channels - Array of channel IDs to send the message to.
 * @param {string}    replyTs  - (Optional) Thread timestamp to reply to (for threading uploads).
 * @return {Promise<void>}
 */
export async function sendMessage(
	client: WebClient,
	text: string,
	channels: string[],
	replyTs: string
) {
	if ( ! text ) {
		Logger.error( 'The text argument is missing.' );
	}
	Logger.startTask(
		`Attempting to send message to ${ channels.length } channels`
	);
	for ( const channel of channels ) {
		try {
			const messagePayload: any = {
				channel,
				text: text.replace( /\\n/g, '\n' ),
				unfurl_links: false,
				unfurl_media: false,
			};
			if ( replyTs ) {
				messagePayload.thread_ts = replyTs;
			}
			const response = await client.chat.postMessage( messagePayload );
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

/**
 * Uploads a file to one or more Slack channels using the provided WebClient.
 * Logs errors for missing files, Slack API errors, or exceptions.
 *
 * @param {WebClient} client   - An instance of Slack WebClient.
 * @param {string}    text     - The initial comment to attach to the file (optional).
 * @param {string}    filePath - Path to the file to upload.
 * @param {string[]}  channels - Array of channel IDs to upload the file to.
 * @param {string}    replyTs  - (Optional) Thread timestamp to reply to (for threading uploads).
 * @return {Promise<void>}
 */
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
			const requestOptions: any = {
				file: filePath,
				filename: basename( filePath ),
				channel_id: channel,
				initial_comment: text
					? text.replace( /\\n/g, '\n' )
					: undefined,
				request_file_info: false,
			};
			if ( replyTs ) {
				requestOptions.thread_ts = replyTs;
			}
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
