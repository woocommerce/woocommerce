/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { execSync } from 'child_process';
import simpleGit from 'simple-git';
import nodePath from 'path';
import { existsSync } from 'fs';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { isGithubCI } from '../core/environment';
import { cloneAuthenticatedRepo, checkoutRemoteBranch } from '../core/git';
import {
	getPullRequestData,
	getShouldAutomateChangelog,
	getChangelogDetails,
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
			Logger.startTask(
				`Getting pull request data for PR number ${ prNumber }`
			);
			const { prBody, headOwner, branch, fileName, head, base } =
				await getPullRequestData( { owner, name }, prNumber );

			const { significance, type, message, comment } =
				getChangelogDetails( prBody );

			Logger.endTask();

			const shouldAutomateChangelog =
				getShouldAutomateChangelog( prBody );

			if ( ! shouldAutomateChangelog ) {
				Logger.notice(
					`PR #${ prNumber } does not have the "Automatically create a changelog entry from the details" checkbox checked. No changelog will be created.`
				);

				process.exit( 0 );
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

			// When a devRepoPath is provided, assume that the dependencies are already installed.
			if ( ! devRepoPath ) {
				Logger.notice( `Installing dependencies in ${ tmpRepoPath }` );
				execSync( 'pnpm install', {
					cwd: tmpRepoPath,
					stdio: 'inherit',
				} );
			}

			Logger.notice( `Checking out remote branch ${ branch }` );
			await checkoutRemoteBranch( tmpRepoPath, branch );

			Logger.notice(
				`Getting all touched projects requiring a changelog`
			);
			const touchedProjectsRequiringChangelog =
				await getTouchedProjectsRequiringChangelog(
					tmpRepoPath,
					base,
					head
				);
			try {
				touchedProjectsRequiringChangelog.forEach(
					( { project, path } ) => {
						if (
							existsSync(
								nodePath.join(
									tmpRepoPath,
									path,
									'changelog',
									fileName
								)
							)
						) {
							Logger.notice(
								`Remove existing changelog file for ${ project }`
							);

							execSync(
								`rm ${ nodePath.join(
									path,
									'changelog',
									fileName
								) }`,
								{
									cwd: tmpRepoPath,
									stdio: 'inherit',
								}
							);
						}

						Logger.notice(
							`Running changelog command for ${ project }`
						);
						const messageExpression = message
							? `-e "${ message }"`
							: '';
						const commentExpression = comment
							? `-c "${ comment }"`
							: '';
						const cmd = `pnpm --filter=${ project } run changelog add -f ${ fileName } -s ${ significance } -t ${ type } ${ messageExpression } ${ commentExpression } -n`;
						execSync( cmd, { cwd: tmpRepoPath, stdio: 'inherit' } );
					}
				);
			} catch ( e ) {
				Logger.error( e );
			}

			Logger.notice(
				`Changelogs created for ${ touchedProjectsRequiringChangelog
					.map( ( p ) => p.project )
					.join( ', ' ) }`
			);

			const git = simpleGit( {
				baseDir: tmpRepoPath,
				config: [ 'core.hooksPath=/dev/null' ],
			} );

			if ( isGithubCI() ) {
				await git.raw(
					'config',
					'--global',
					'user.email',
					'github-actions@github.com'
				);
				await git.raw(
					'config',
					'--global',
					'user.name',
					'github-actions'
				);
			}

			const shortStatus = await git.raw( [ 'status', '--short' ] );

			if ( shortStatus.length === 0 ) {
				Logger.notice(
					`No changes in changelog files. Skipping commit and push.`
				);
				process.exit( 0 );
			}

			Logger.notice( `Adding and committing changes` );
			await git.add( '.' );
			await git.commit( 'Adding changelog from automation.' );
			await git.push( 'origin', branch );
			Logger.notice( `Pushed changes to ${ branch }` );
		}
	);

export default program;
