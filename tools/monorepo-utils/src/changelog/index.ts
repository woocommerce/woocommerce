/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { execSync } from 'child_process';
import simpleGit from 'simple-git';

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
			const { prBody, headOwner, branch, fileName, head, base } =
				await getPullRequestData( { owner, name }, prNumber );

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
			const { significance, type, message, comment } =
				getChangelogDetails( prBody );

			touchedProjectsRequiringChangelog.forEach( ( project ) => {
				Logger.notice( `Running changelog command for ${ project }` );
				const cmd = `pnpm --filter=${ project } run changelog add -f ${ fileName } -s ${ significance } -t ${ type } -e "${ message }" -c "${ comment }" -n`;
				execSync( cmd, { cwd: tmpRepoPath, stdio: 'inherit' } );
			} );

			Logger.notice(
				`Changelogs created for ${ touchedProjectsRequiringChangelog.join(
					', '
				) }`
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

			Logger.notice( `Adding and committing changes` );
			await git.add( '.' );
			await git.commit( 'Adding changelog from automation.' );
			await git.push( 'origin', branch );

			Logger.notice( `Pushed changes to ${ branch }` );
		}
	);

export default program;
