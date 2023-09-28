/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';
import nodePath from 'path';
import { existsSync, readFileSync, rmSync, writeFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { Logger } from '../core/logger';
import { isGithubCI } from '../core/environment';
import { cloneAuthenticatedRepo, checkoutRemoteBranch } from '../core/git';
import {
	getPullRequestData,
	shouldAutomateChangelog,
	getChangelogDetails,
	getChangelogDetailsError,
} from './lib/github';
import {
	getAllProjectPaths,
	getTouchedProjectsRequiringChangelog,
} from './lib/projects';

const program = new Command( 'changefile' )
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

			Logger.endTask();

			if ( ! shouldAutomateChangelog( prBody ) ) {
				Logger.notice(
					`PR #${ prNumber } does not have the "Automatically create a changelog entry from the details" checkbox checked. No changelog will be created.`
				);

				process.exit( 0 );
			}

			const details = getChangelogDetails( prBody );
			const { significance, type, message, comment } = details;
			const changelogDetailsError = getChangelogDetailsError( details );

			if ( changelogDetailsError ) {
				Logger.error( changelogDetailsError );
			}

			Logger.startTask(
				`Making a temporary clone of '${ headOwner }/${ name }'`
			);
			const tmpRepoPath = devRepoPath
				? devRepoPath
				: await cloneAuthenticatedRepo(
						{ owner: headOwner, name },
						false
				  );

			Logger.endTask();

			Logger.notice(
				`Temporary clone of '${ headOwner }/${ name }' created at ${ tmpRepoPath }`
			);

			// If a pull request is coming from a contributor's fork's trunk branch, we don't nee to checkout the remote branch because its already available as part of the clone.
			if ( branch !== 'trunk' ) {
				Logger.notice( `Checking out remote branch ${ branch }` );
				await checkoutRemoteBranch( tmpRepoPath, branch, false );
			}

			Logger.notice(
				`Getting all touched projects requiring a changelog`
			);

			const touchedProjectsRequiringChangelog =
				await getTouchedProjectsRequiringChangelog(
					tmpRepoPath,
					base,
					head,
					fileName,
					owner,
					name
				);

			try {
				const allProjectPaths = await getAllProjectPaths( tmpRepoPath );

				Logger.notice(
					'Removing existing changelog files in case a change is reverted and the entry is no longer needed'
				);
				allProjectPaths.forEach( ( projectPath ) => {
					const composerFilePath = nodePath.join(
						tmpRepoPath,
						projectPath,
						'composer.json'
					);
					if ( ! existsSync( composerFilePath ) ) {
						return;
					}

					// Figure out where the changelog files belong for this project.
					const composerFile = JSON.parse(
						readFileSync( composerFilePath, {
							encoding: 'utf-8',
						} )
					);
					const changelogFilePath = nodePath.join(
						tmpRepoPath,
						projectPath,
						composerFile.extra?.changelogger[ 'changes-dir' ] ??
							'changelog',
						fileName
					);

					if ( ! existsSync( changelogFilePath ) ) {
						return;
					}

					Logger.notice(
						`Remove existing changelog file ${ changelogFilePath }`
					);

					rmSync( changelogFilePath );
				} );

				if ( ! touchedProjectsRequiringChangelog ) {
					Logger.notice( 'No projects require a changelog' );
					process.exit( 0 );
				}

				for ( const project in touchedProjectsRequiringChangelog ) {
					const projectPath = nodePath.join(
						tmpRepoPath,
						touchedProjectsRequiringChangelog[ project ]
					);

					Logger.notice(
						`Generating changefile for ${ project } (${ projectPath }))`
					);

					// Figure out where the changelog file belongs for this project.
					const composerFile = JSON.parse(
						readFileSync(
							nodePath.join( projectPath, 'composer.json' ),
							{ encoding: 'utf-8' }
						)
					);
					const changelogFilePath = nodePath.join(
						projectPath,
						composerFile.extra?.changelogger[ 'changes-dir' ] ??
							'changelog',
						fileName
					);

					// Write the changefile using the correct format.
					let fileContent = `Significance: ${ significance }\n`;
					fileContent += `Type: ${ type }\n`;
					if ( comment ) {
						fileContent += `Comment: ${ comment }\n`;
					}
					fileContent += `\n${ message }`;
					writeFileSync( changelogFilePath, fileContent );
				}
			} catch ( e ) {
				Logger.error( e );
			}

			const touchedProjectsString = Object.keys(
				touchedProjectsRequiringChangelog
			).join( ', ' );

			Logger.notice(
				`Changelogs created for ${ touchedProjectsString }`
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
			await git.commit(
				`Add changefile(s) from automation for the following project(s): ${ touchedProjectsString }`
			);
			await git.push( 'origin', branch );
			Logger.notice( `Pushed changes to ${ branch }` );
		}
	);

export default program;
