/**
 * External dependencies
 */
import fs from 'fs';
import {
	ChatPostMessageResponse,
	FilesUploadResponse,
	WebClient,
} from '@slack/web-api';

/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';

interface Options {
	reportName: string;
	username: string;
	isFailure: boolean;
	eventName: string;
	sha: string;
	commitMessage: string;
	prTitle: string;
	prNumber: string;
	actor: string;
	triggeringActor: string;
	runId: string;
	runAttempt: string;
	serverUrl: string;
	repository: string;
	refType: string;
	refName: string;
}

/**
 * Returns a Slack context block element with a given text.
 *
 * @param {string} text - the text of the element
 * @return {Object} - the block element
 */
function getTextContextElement( text: string ): object {
	return {
		type: 'plain_text',
		text,
		emoji: false,
	};
}

/**
 * Returns a Slack button element with a given text and url.
 *
 * @param {string} text - the text of the button
 * @param {string} url  - the url of the button
 * @return {Object} - the button element
 */
function getButton( text: string, url: string ): object {
	return {
		type: 'button',
		text: {
			type: 'plain_text',
			text,
		},
		url,
	};
}

/**
 * Creates and returns a run url
 *
 * @param {Options} options     - the options object
 * @param {boolean} withAttempt - whether to include the run attempt in the url
 * @return {string} the run url
 */
export function getRunUrl( options: Options, withAttempt: boolean ): string {
	const { serverUrl, runId, repository, runAttempt } = options;
	return `${ serverUrl }/${ repository }/actions/runs/${ runId }${
		withAttempt ? `/attempts/${ runAttempt }` : ''
	}`;
}

/**
 * Returns an object with notification data.
 * Properties: `text` for notification's text and `id` for a unique identifier for the message.
 * that can be used later on to find this message and update it or send replies.
 *
 * @param {Options} options - whether the workflow is failed or not
 */
export async function createMessage( options: Options ) {
	const {
		sha,
		eventName,
		actor,
		prNumber,
		prTitle,
		runId,
		commitMessage,
		reportName,
		runAttempt,
		triggeringActor,
		serverUrl,
		repository,
		refType,
		refName,
	} = options;

	let target = `for ${ sha }`;
	const contextElements = [];
	const buttons = [];

	const lastRunBlock = getTextContextElement(
		`Run: ${ runId }/${ runAttempt }, triggered by ${ triggeringActor }`
	);
	const actorBlock = getTextContextElement( `Actor: ${ actor }` );
	const lastRunButtonBlock = getButton( 'Run', getRunUrl( options, false ) );
	buttons.push( lastRunButtonBlock );

	if ( eventName === 'pull_request' ) {
		target = `for pull request *#${ prNumber }*`;

		contextElements.push(
			getTextContextElement( `Title: ${ prTitle }` ),
			actorBlock
		);
		buttons.push(
			getButton(
				`PR #${ prNumber }`,
				`${ serverUrl }/${ repository }/pull/${ prNumber }`
			)
		);
	}

	if (
		[ 'push', 'workflow_run', 'workflow_call', 'schedule' ].includes(
			eventName
		)
	) {
		target = `on ${ refType } _*${ refName }*_ (${ eventName })`;
		const truncatedMessage =
			commitMessage.length > 50
				? commitMessage.substring( 0, 50 ) + '...'
				: commitMessage;

		contextElements.push(
			getTextContextElement(
				`Commit: ${ sha.substring( 0, 8 ) } ${ truncatedMessage }`
			),
			actorBlock
		);
		buttons.push(
			getButton(
				`Commit ${ sha.substring( 0, 8 ) }`,
				`${ serverUrl }/${ repository }/commit/${ sha }`
			)
		);
	}

	if ( eventName === 'repository_dispatch' ) {
		target = `for event _*${ eventName }*_`;
	}

	contextElements.push( lastRunBlock );

	const reportText = reportName ? `_*${ reportName }*_ failed` : 'Failure';
	const text = `:x:	${ reportText } ${ target }`;

	const mainMsgBlocks = [
		{
			type: 'section',
			text: {
				type: 'mrkdwn',
				text,
			},
		},
		{
			type: 'context',
			elements: contextElements,
		},
		{
			type: 'actions',
			elements: buttons,
		},
	];

	const detailsMsgBlocksChunks = [];
	// detailsMsgBlocksChunks.push( ...getPlaywrightBlocks() );

	return { text, mainMsgBlocks, detailsMsgBlocksChunks };
}

/**
 * Split an array of blocks into chunks of a given size
 *
 * @param {[object]} blocks    - the array to be split
 * @param {number}   chunkSize - the maximum size of each chunk
 * @return {any[]} the array of chunks
 */
export function getBlocksChunksBySize(
	blocks: any[],
	chunkSize: number
): any[] {
	const chunks = [];
	for ( let i = 0; i < blocks.length; i += chunkSize ) {
		const chunk = blocks.slice( i, i + chunkSize );
		chunks.push( chunk );
	}
	return chunks;
}

/**
 * Split an array of blocks into chunks based on a given type property as delimiter
 * E.g. if the array is [ {type: 'context'}, {type: 'context'}, {type: 'file'}, {type: 'context'} ] and the delimiter is 'file'
 * the result will be [ [ {type: 'context'}, {type: 'context'} ], [ {type: 'file'} ], [ {type: 'context'} ] ]
 *
 * @param {[object]} blocks - the array to be split
 * @param {string}   type   - the type property to use as delimiter
 * @return {any[]} the array of chunks
 */
export function getBlocksChunksByType(
	blocks: string | any[],
	type: string
): any[] {
	const chunks = [];
	let nextIndex = 0;

	for ( let i = 0; i < blocks.length; i++ ) {
		if ( blocks[ i ].type === type ) {
			if ( nextIndex < i ) {
				chunks.push( blocks.slice( nextIndex, i ) );
			}
			chunks.push( blocks.slice( i, i + 1 ) );
			nextIndex = i + 1;
		}
	}

	if ( nextIndex < blocks.length ) {
		chunks.push( blocks.slice( nextIndex ) );
	}

	return chunks;
}

/**
 * Split an array of blocks into chunks based on a given type property as delimiter and a max size
 *
 * @param {[object]} blocks        - the array to be split
 * @param {number}   maxSize       - the maximum size of each chunk
 * @param {string}   typeDelimiter - the type property to use as delimiter
 * @return {[any]} the array of chunks
 */
function getBlocksChunks(
	blocks: [ object ],
	maxSize: number,
	typeDelimiter: string
): any[] {
	const chunksByType = getBlocksChunksByType( blocks, typeDelimiter );
	const chunks = [];

	for ( const chunk of chunksByType ) {
		// eslint-disable-next-line no-unused-expressions
		chunk.length > maxSize
			? chunks.push( ...getBlocksChunksBySize( chunk, maxSize ) )
			: chunks.push( chunk );
	}

	return chunks;
}

export async function postMessage( client: WebClient, options: any ) {
	const {
		text,
		blocks = [],
		channel,
		username,
		icon_emoji,
		ts,
		thread_ts,
	} = options;

	const method = 'postMessage';
	let response: FilesUploadResponse | ChatPostMessageResponse;

	// Split the blocks into chunks:
	// - blocks with type 'file' are separate chunks. 'file' type is not a valid block, and when we have one we need to call files.upload instead of chat.postMessage.
	// - chunk max size is 50 blocks, Slack API will fail if we send more
	const chunks = getBlocksChunks( blocks, 50, 'file' );

	for ( const chunk of chunks ) {
		// The expectation is that chunks with files will only have one element
		if ( chunk[ 0 ].type === 'file' ) {
			if ( ! fs.existsSync( chunk[ 0 ].path ) ) {
				Logger.error( 'File not found: ' + chunk[ 0 ].path );
				continue;
			}

			try {
				response = await client.files.upload( {
					file: fs.createReadStream( chunk[ 0 ].path ),
					channels: channel,
					thread_ts,
				} );
			} catch ( err ) {
				Logger.error( err );
			}
		} else {
			try {
				response = await client.chat[ method ]( {
					text,
					blocks: chunk,
					channel,
					ts,
					thread_ts,
					username,
					icon_emoji,
					unfurl_links: false,
					unfurl_media: false,
				} );
			} catch ( err ) {
				Logger.error( err );
			}
		}
	}

	return response;
}
