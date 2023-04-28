/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';
import { execSync } from 'child_process';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { getEnvVar } from '../../../core/environment';
import { cloneRepoShallow } from '../../../core/git';

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
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
	.action( async ( options ) => {
		const { owner, name, version } = options;
		const releaseBranch = `release/${ version }`;

		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		const source = `github.com/${ owner }/${ name }`;
		const token = getEnvVar( 'GITHUB_TOKEN' );
		const remote = `https://${ owner }:${ token }@${ source }`;
		const tmpRepoPath = await cloneRepoShallow( remote );
		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );

		// The clone is shallow, so we need to call this before fetching.
		await git.raw( [
			'remote',
			'set-branches',
			'--add',
			'origin',
			releaseBranch,
		] );
		await git.raw( [ 'fetch', 'origin', releaseBranch ] );
		await git.raw( [
			'checkout',
			'-b',
			releaseBranch,
			`origin/${ releaseBranch }`,
		] );

		// Checkout new branch here.

		execSync( 'pnpm install', {
			cwd: tmpRepoPath,
		} );

		execSync(
			`pnpm --filter=woocommerce run changelog write --add-pr-num -n -vvv --use-version ${ version }`,
			{
				cwd: tmpRepoPath,
			}
		);
	} );
