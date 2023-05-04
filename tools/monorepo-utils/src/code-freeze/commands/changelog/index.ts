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
		'-d --devRepoPath <devRepoPath>',
		'Path to existing repo. Use this option to avoid cloning a fresh repo and installing all the dependencies. Note that using this option assumes dependencies are already installed.'
	)
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
	.action( async ( options ) => {
		const { owner, name, version, devRepoPath } = options;
		const tmpRepoPath = devRepoPath
			? devRepoPath
			: await cloneAuthenticatedRepo( options, true );

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
		const deletionCommitHash = await updateReleaseBranchChangelogs(
			options,
			tmpRepoPath,
			releaseBranch
		);

		await updateTrunkChangelog( tmpRepoPath, version, deletionCommitHash );
	} );
