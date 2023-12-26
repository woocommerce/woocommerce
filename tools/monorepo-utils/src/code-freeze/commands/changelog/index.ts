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
	.description( 'Make changelog pull requests to trunk and release branch' )
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
	.option(
		'-c --commit-direct-to-base',
		'Commit directly to the base branch. Do not create a PR just push directly to base branch',
		false
	)
	.option(
		'-o, --override <override>',
		"Time Override: The time to use in checking whether the action should run (default: 'now').",
		'now'
	)
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
	.action( async ( options: Options ) => {
		const { owner, name, version, devRepoPath } = options;
		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);

		const cloneOptions = {
			owner: owner ? owner : 'woocommerce',
			name: name ? name : 'woocommerce',
		};
		// Use a supplied path, otherwise do a full clone of the repo, including history so that changelogs can be created with links to PRs.
		const tmpRepoPath = devRepoPath
			? devRepoPath
			: await cloneAuthenticatedRepo( cloneOptions, false );

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
