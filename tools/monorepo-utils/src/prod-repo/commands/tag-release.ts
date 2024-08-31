/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';
import path from 'node:path';

/**
 * Internal dependencies
 */
import { Logger } from '../../core/logger';
import {
	getProjectPathFromFilter,
	getTargetRepoFromConfig,
	getTargetBranchFromConfig,
	runBuildCommand,
	getArchiveFromConfig,
	extractZipTo,
} from '../lib/utils';
import {
	cloneAuthenticatedRepo,
	createEmptyOrphanBranch,
	pushBranchToRemote,
} from '../../core/git';

export const tagReleaseCommand = new Command( 'tag-release' )
	.description( 'Tag the branch as a release with the latest version.' )
	.argument( '<project>', 'The project to release.' )
	.argument( '<version>', 'The version to tag.' )
	.option(
		'-o, --owner <owner>',
		'Repository owner. Default: woocommerce.',
		'woocommerce'
	)
	.option(
		'-t, --target-repo <target>',
		'Target repository. Defaults to the configured target repository from project config.',
		''
	)
	.option(
		'-b, --target-branch <branch>',
		'Branch from which to tag release. Defaults to the configured target branch from project config.',
		''
	)
	.option( '-d, --dry-run', 'Skips the pushing and tagging the release.' )
	.action(
		async (
			project,
			version,
			{ owner, targetRepo, targetBranch, dryRun }
		) => {
			const projectPath = getProjectPathFromFilter( project );
			if ( ! projectPath ) {
				Logger.error(
					'The project specified should match exactly one pnpm filter'
				);
			}

			let repo = targetRepo;
			if ( ! repo ) {
				repo = getTargetRepoFromConfig( projectPath );
			}

			let branch = targetBranch;
			if ( ! branch ) {
				branch = getTargetBranchFromConfig( projectPath );
			}

			const tmpRepoPath = await cloneAuthenticatedRepo(
				{ owner, name: repo },
				false
			);
			const tmpBranchName = `tmp-${ branch }`;
			await createEmptyOrphanBranch( tmpRepoPath, tmpBranchName, true );

			runBuildCommand( project );
			const packageArchive = getArchiveFromConfig( projectPath );
			await extractZipTo( packageArchive, tmpRepoPath );

			const git = simpleGit( {
				baseDir: tmpRepoPath,
				config: [ 'core.hooksPath=/dev/null' ],
			} );

			git.raw( [ 'add', '.' ] );
			git.raw( [ 'commit', '-m', version ] );

			if ( dryRun ) {
				// @todo: output for dry run
			}
		}
	);