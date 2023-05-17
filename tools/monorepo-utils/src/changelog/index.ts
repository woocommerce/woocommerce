/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { setOutput } from '@actions/core';
import { execSync } from 'child_process';

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
} from './lib/github';
import { getTouchedProjectsRequiringChangelog } from './lib/projects';

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
			const {
				prBody,
				isCommunityPR,
				headOwner,
				branch,
				fileName,
				head,
				base,
			} = await getPullRequestData( { owner, name }, prNumber );

			const shouldAutomateChangelog =
				getShouldAutomateChangelog( prBody );

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

			const significance = getChangelogSignificance( prBody );
			const type = getChangelogType( prBody );
			const message = getChangelogMessage( prBody );
			const comment = getChangelogComment( prBody );

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
				head,
				base,
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

			Logger.notice( `Checking out remote branch ${ branch }` );
			await checkoutRemoteBranch( tmpRepoPath, branch );

			Logger.notice(
				`Getting all touched projects requiring a changelog`
			);
			const touchedProjectsRequiringChangelog =
				await getTouchedProjectsRequiringChangelog(
					tmpRepoPath,
					base,
					head,
					fileName
				);

			touchedProjectsRequiringChangelog.forEach( ( project ) => {
				Logger.notice( `Running changelog command for ${ project }` );
				const cmd = `pnpm --filter=${ project } run changelog add -f ${ fileName } -s ${ significance } -t ${ type } -e "${ message }" -n`;
				execSync( cmd, { cwd: tmpRepoPath } );
			} );
		}
	);

export default program;
