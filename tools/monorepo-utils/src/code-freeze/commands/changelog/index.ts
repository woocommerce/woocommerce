/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { cloneAuthenticatedRepo } from '../../../core/git';
import { updateTrunkChangelog, updateReleaseBranchChangelogs } from './lib';
import { Options } from './types';

export const changelogCommand = new Command( 'changelog' )
	.description( 'Create a new release branch' )
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
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
	.action( async ( options: Options ) => {
		const { owner, name, version, devRepoPath } = options;
		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		// Use a supplied path, otherwise do a full clone of the repo, including history so that changelogs can be created with links to PRs.
		const tmpRepoPath = devRepoPath
			? devRepoPath
			: await cloneAuthenticatedRepo( options, false );

		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		// When a devRepoPath is provided, assume that the dependencies are already installed.
		if ( ! devRepoPath ) {
			Logger.notice( `Installing dependencies in ${ tmpRepoPath }` );
			execSync( 'pnpm install --filter woocommerce', {
				cwd: tmpRepoPath,
				stdio: 'inherit',
			} );
		}

		const releaseBranch = `release/${ version }`;

		// Update the release branch.
		const releaseBranchChanges = await updateReleaseBranchChangelogs(
			options,
			tmpRepoPath,
			releaseBranch
		);

		// Update trunk.
		await updateTrunkChangelog(
			options,
			tmpRepoPath,
			releaseBranch,
			releaseBranchChanges
		);
	} );
