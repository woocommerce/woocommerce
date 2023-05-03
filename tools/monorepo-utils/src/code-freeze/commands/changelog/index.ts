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

const gitCheckoutRemoteBranch = async ( gitInstance, newBranch ) => {
	// The clone is shallow, so we need to call this before fetching.
	await gitInstance.raw( [
		'remote',
		'set-branches',
		'--add',
		'origin',
		newBranch,
	] );
	await gitInstance.raw( [ 'fetch', 'origin', newBranch ] );
	await gitInstance.raw( [
		'checkout',
		'-b',
		newBranch,
		`origin/${ newBranch }`,
	] );
};

const updateReleaseBranchChangelogs = async (
	git,
	releaseBranch,
	tmpRepoPath,
	version
) => {
	await gitCheckoutRemoteBranch( git, releaseBranch );

	await git.checkout( {
		'-b': null,
		[ `update/${ version }-changelog` ]: null,
	} );

	Logger.notice( `Running the changelog script in ${ tmpRepoPath }` );
	execSync(
		`pnpm --filter=woocommerce run changelog write --add-pr-num -n -vvv --use-version ${ version }`,
		{
			cwd: tmpRepoPath,
			stdio: 'inherit',
		}
	);

	//Checkout pnpm-lock.yaml to prevent issues in case of an out of date lockfile.
	await git.checkout( 'pnpm-lock.yaml' );

	Logger.notice( `Committing deleted files in ${ tmpRepoPath }` );

	await git.add( 'plugins/woocommerce/changelog/' );
	await git.commit( `Delete changelog files from ${ version } release` );

	const deletionCommitHash = await git.raw( [ 'rev-parse', 'HEAD' ] );
	Logger.notice( `git deletion hash: ${ deletionCommitHash }` );

	// Do readme.txt changes
	Logger.notice( `Updating readme.txt in ${ tmpRepoPath }` );
	execSync( 'php .github/workflows/scripts/release-changelog.php', {
		cwd: tmpRepoPath,
		stdio: 'inherit',
	} );
	await git.add( 'plugins/woocommerce/readme.txt' );
	await git.commit( `Update the readme files for the ${ version } release` );
	// Push changes here.
	await git.checkout( '.' );
	// Create PR here

	return deletionCommitHash;
};

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

		// Logger.startTask(
		// 	`Making a temporary clone of '${ owner }/${ name }'`
		// );
		// const source = `github.com/${ owner }/${ name }`;
		// const token = getEnvVar( 'GITHUB_TOKEN' );
		// const remote = `https://${ owner }:${ token }@${ source }`;

		// // We need the full history here to get the changelog PRs.
		// const tmpRepoPath = await cloneRepoShallow( remote );
		// Logger.endTask();

		const tmpRepoPath = '/Users/paulsealock/Workspace/tmp/woocommerce';

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );

		// Logger.notice( `Installing dependencies in ${ tmpRepoPath }` );
		// execSync( 'pnpm install --filter woocommerce', {
		// 	cwd: tmpRepoPath,
		// 	stdio: 'inherit',
		// } );

		const deletionCommitHash = await updateReleaseBranchChangelogs(
			git,
			releaseBranch,
			tmpRepoPath,
			version
		);
	} );
