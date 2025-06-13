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
import {
	getConfiguredChannels,
	loadConfig,
	parseConfig,
	resolveChannels,
} from './lib/config';

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
	.option(
		'--config <configPath>',
		'Path to a JSON config file containing notification rules or settings',
		''
	)
	.action( async ( options ) => {
		if ( options.reportName === '' ) {
			Logger.warn(
				'No report name was specified. Using a default message.'
			);
		}

		const channels: string[] = [];

		if ( options.config ) {
			try {
				const configContent = loadConfig( options.config );
				const reporterConfig = parseConfig( configContent );
				const refName = getEnvVar( 'GITHUB_REF_NAME', true );
				const configuredChannels = getConfiguredChannels(
					reporterConfig,
					refName,
					options.reportName
				);
				channels.push( ...resolveChannels( configuredChannels ) );
			} catch ( error ) {
				Logger.error(
					`Failed to determine channels to send the notification to: ${ error.message }`
				);
				process.exit( 1 );
			}
		} else {
			const defaultChannel = getEnvVar( 'DEFAULT_CHECKS_CHANNEL', true );
			channels.push( defaultChannel );
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
					sha: getEnvVar( 'GITHUB_SHA', true ),
					commitMessage: options.commitMessage,
					prTitle: options.prTitle,
					prNumber: options.prNumber,
					actor: getEnvVar( 'GITHUB_ACTOR', true ),
					triggeringActor: getEnvVar(
						'GITHUB_TRIGGERING_ACTOR',
						true
					),
					eventName: getEnvVar( 'GITHUB_EVENT_NAME', true ),
					runId: getEnvVar( 'GITHUB_RUN_ID', true ),
					runAttempt: getEnvVar( 'GITHUB_RUN_ATTEMPT', true ),
					serverUrl: getEnvVar( 'GITHUB_SERVER_URL', true ),
					repository: getEnvVar( 'GITHUB_REPOSITORY', true ),
					refType: getEnvVar( 'GITHUB_REF_TYPE', true ),
					refName: getEnvVar( 'GITHUB_REF_NAME', true ),
				} );

			for ( const channel of channels ) {
				Logger.notice( 'Sending new message' );
				// Send a new main message
				const response = await postMessage( client, {
					text: `${ text }`,
					blocks: mainMsgBlocks,
					channel,
					username,
				} );
				const mainMessageTS = response.ts;

				if ( detailsMsgBlocksChunks.length > 0 ) {
					Logger.notice( 'Replying with failure details' );
					// Send replies to the main message with the current failure result
					await postMessage( client, {
						text,
						blocks: detailsMsgBlocksChunks,
						channel,
						username,
						thread_ts: mainMessageTS,
					} );
				}
			}

			if ( channels.length === 0 ) {
				Logger.error(
					'No channels found. Please check your configuration.'
				);
				process.exit( 1 );
			}
		} else {
			Logger.notice(
				`No message will be sent for '${ options.conclusion }'`
			);
		}
	} );

export default program;
