/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { WebClient } from '@slack/web-api';

/**
 * External dependencies
 */
import { existsSync } from 'fs';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';

export const slackMessageCommand = new Command( 'message' )
	.description( 'Send a file upload message to a slack channel' )
	.argument(
		'<token>',
		'Slack authentication token bearing required scopes.'
	)
	.argument( '<text>', 'Text based message to send to the slack channel.' )
	.argument( '<file>', 'File path to upload to the slack channel.' )
	.argument(
		'<channels...>',
		'Slack channels to send the message to. Pass as many as you like.'
	)
	.option(
		'--dont-fail',
		'Do not fail the command if a message fails to send to any channel.'
	)
	.action( async ( token, text, file, channels, { dontFail } ) => {
		Logger.startTask(
			`Attempting to send message to Slack for channels: ${ channels.join(
				','
			) }`
		);

		const shouldFail = ! dontFail;

		if ( file && ! existsSync( file ) ) {
			Logger.error(
				`Unable to open file with path: ${ file }`,
				shouldFail
			);
		}

		const client = new WebClient( token );
		for ( const channel of channels ) {
			try {
				// @ts-expect-error - types are not up to date.
				await client.files.uploadV2( {
					file,
					filename: basename( file ),
					channels: channel,
					initial_comment: text.replace( /\\n/g, '\n' ),
				} );

				Logger.notice(
					`Successfully uploaded ${ file } to channel: ${ channel }`
				);
			} catch ( e ) {
				Logger.error( e, shouldFail );
			}
		}

		Logger.endTask();
	} );
