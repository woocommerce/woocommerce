/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { getEnvVar } from '../../../core/environment';
import { cloneRepoShallow, checkoutRemoteBranch } from '../../../core/git';
import { createPR } from '../../../core/github/repo';

const updateReleaseBranchChangelogs = async (
	git,
	tmpRepoPath,
	version,
	releaseBranch
) => {
	await checkoutRemoteBranch( tmpRepoPath, releaseBranch );

	const branch = `update/${ version }-changelog`;

	await git.checkout( {
		'-b': null,
		[ branch ]: null,
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
	await git.push( 'origin', branch );
	await git.checkout( '.' );

	Logger.notice( `Creating PR for ${ branch }` );
	createPR(
		branch,
		'trunk',
		'psealock',
		'woocommerce',
		`Update changelog for ${ version } release`,
		'This is a body'
	);

	return deletionCommitHash.trim();
};

const updateTrunkChangelog = async (
	git,
	tmpRepoPath,
	version,
	deletionCommitHash
) => {
	Logger.notice( `Deleting changelogs from trunk ${ tmpRepoPath }` );
	await git.checkout( 'trunk' );
	const branch = `delete/${ version }-changelog`;
	await git.checkout( {
		'-b': null,
		[ branch ]: null,
	} );
	await git.raw( [ 'cherry-pick', deletionCommitHash ] );
	await git.push( 'origin', branch );

	Logger.notice( `Creating PR for ${ branch }` );
	createPR(
		branch,
		'trunk',
		'psealock',
		'woocommerce',
		`Delete changelog for ${ version } release`,
		'This is a body'
	);
};

const clone = async ( options ) => {
	const { owner, name } = options;
	Logger.startTask( `Making a temporary clone of '${ owner }/${ name }'` );
	const source = `github.com/${ owner }/${ name }`;
	const token = getEnvVar( 'GITHUB_TOKEN' );
	const remote = `https://${ owner }:${ token }@${ source }`;

	// We need the full history here to get the changelog PRs.
	const tmpRepoPath = await cloneRepoShallow( remote );
	Logger.endTask();
	return tmpRepoPath;
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
	.option(
		'-d --devRepoPath <devRepoPath>',
		'Path to existing repo. Use this option to avoid cloning a fresh repo and installing all the dependencies. Note that using this option assumes dependencies are already installed.'
	)
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
	.action( async ( options ) => {
		const { owner, name, version, devRepoPath } = options;
		const tmpRepoPath = devRepoPath ? devRepoPath : await clone( options );

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );

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
			git,
			tmpRepoPath,
			version,
			releaseBranch
		);

		await updateTrunkChangelog(
			git,
			tmpRepoPath,
			version,
			deletionCommitHash
		);
	} );
