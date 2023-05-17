/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { isGithubCI } from '../core/environment';
import { cloneAuthenticatedRepo, checkoutRemoteBranch } from '../core/git';
import {
	getPullRequestData,
	getChangelogSignificance,
	getShouldAutomateChangelog,
	getChangelogType,
	getChangelogMessage,
	getChangelogComment,
	getChangeloggerProjects,
} from './lib';

const program = new Command( 'changelog' )
	.description( 'Changelog utilities' )
	.option(
		'-o --owner <owner>',
		'Repository owner. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-n --name <name>',
		'Repository name. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-d --dev-repo-path <devRepoPath>',
		'Path to existing repo. Use this option to avoid cloning a fresh repo for development purposes. Note that using this option assumes dependencies are already installed.'
	)
	.argument( '<pr-number>', 'Pull request number' )
	.action(
		async (
			prNumber: string,
			options: {
				owner: string;
				name: string;
				devRepoPath?: string;
			}
		) => {
			const { owner, name, devRepoPath } = options;
			const { prData, isCommunityPR, headOwner, branch, fileName } =
				await getPullRequestData( { owner, name }, prNumber );

			const shouldAutomateChangelog = getShouldAutomateChangelog(
				prData.body
			);

			const isGithub = isGithubCI();

			if ( ! shouldAutomateChangelog ) {
				Logger.notice(
					`PR #${ prNumber } does not have the "Automatically create a changelog entry from the details" checkbox checked. No changelog will be created.`
				);

				if ( isGithub ) {
					setOutput( 'extractedData', null );
				}

				process.exit( 0 );
			}

			const significance = getChangelogSignificance( prData.body );
			const type = getChangelogType( prData.body );
			const message = getChangelogMessage( prData.body );
			const comment = getChangelogComment( prData.body );

			const extractedData = {
				prNumber,
				headOwner,
				isCommunityPR,
				branch,
				fileName,
				significance,
				type,
				message,
				comment,
			};

			Logger.notice( JSON.stringify( extractedData, null, 2 ) );

			if ( isGithubCI() ) {
				setOutput( 'extractedData', JSON.stringify( extractedData ) );
			}

			Logger.startTask(
				`Making a temporary clone of '${ headOwner }/${ name }'`
			);
			const tmpRepoPath = devRepoPath
				? devRepoPath
				: await cloneAuthenticatedRepo(
						{ owner: headOwner, name },
						true
				  );

			Logger.endTask();

			Logger.notice(
				`Temporary clone of '${ headOwner }/${ name }' created at ${ tmpRepoPath }`
			);

			// Logger.notice( `Checking out remote branch ${ branch }` );
			// await checkoutRemoteBranch( tmpRepoPath, branch );

			const changeloggerProjects = await getChangeloggerProjects(
				tmpRepoPath
			);
		}
	);

export default program;
