/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { WebClient } from '@slack/web-api';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { getEnvVar } from '../core/environment';
import { createMessage, postMessage } from './lib/message';

const conclusions = [ 'success', 'failure', 'skipped', 'cancelled' ];

const program = new Command( 'slack-test-report' )
	.description( 'Send a test report to Slack' )
	.requiredOption(
		'-c --conclusion <conclusion>',
		`Test run conclusion. Expected one of: ${ conclusions }`
	)
	.option(
		'-r --report-name <reportName>',
		'The name of the report. Example: "post-merge tests", "daily e2e tests"',
		''
	)
	.option(
		'-u --username <username>',
		'The Slack username.',
		'Github reporter'
	)
	.option(
		'-n --pr-number <prNumber>',
		'The PR number to be included in the message, if the event is pull_request.',
		''
	)
	.option(
		'-t --pr-title <prTitle>',
		'The PR title to be included in the message, if the event is pull_request.',
		'Default PR title'
	)
	.option( '-m --commit-message <commitMessage>', 'The commit message.', '' )
	.action( async ( options ) => {
		if ( options.reportName === '' ) {
			Logger.warn(
				'No report name was specified. Using a default message.'
			);
		}

		const isFailure = options.conclusion === 'failure';

		if ( isFailure ) {
			const { username } = options;
			const client = new WebClient( getEnvVar( 'SLACK_TOKEN', true ) );
			const { text, mainMsgBlocks, detailsMsgBlocksChunks } =
				await createMessage( {
					isFailure,
					reportName: options.reportName,
					username: options.username,
					sha: getEnvVar( 'GITHUB_SHA' ),
					commitMessage: options.commitMessage,
					prTitle: options.prTitle,
					prNumber: options.prNumber,
					actor: getEnvVar( 'GITHUB_ACTOR' ),
					triggeringActor: getEnvVar( 'GITHUB_TRIGGERING_ACTOR' ),
					eventName: getEnvVar( 'GITHUB_EVENT_NAME' ),
					runId: getEnvVar( 'GITHUB_RUN_ID' ),
					runAttempt: getEnvVar( 'GITHUB_RUN_ATTEMPT' ),
					serverUrl: getEnvVar( 'GITHUB_SERVER_URL' ),
					repository: getEnvVar( 'GITHUB_REPOSITORY' ),
					refType: getEnvVar( 'GITHUB_REF_TYPE' ),
					refName: getEnvVar( 'GITHUB_REF_NAME' ),
				} );

			Logger.notice( 'Sending new message' );
			// Send a new main message
			const response = await postMessage( client, {
				text: `${ text }`,
				blocks: mainMsgBlocks,
				channel: getEnvVar( 'SLACK_CHANNEL' ),
				username,
			} );
			const mainMessageTS = response.ts;

			if ( detailsMsgBlocksChunks.length === 0 ) {
				Logger.notice(
					'Sending new reply to main message with failure details'
				);
				// Send replies to the main message with the current failure result
				await postMessage( client, {
					text,
					blocks: detailsMsgBlocksChunks,
					channel: getEnvVar( 'SLACK_CHANNEL' ),
					username,
					thread_ts: mainMessageTS,
				} );
			}
		} else {
			Logger.notice( 'Test run passed. No message will be sent.' );
		}
	} );

export default program;
