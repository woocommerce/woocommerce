/**
 * External dependencies
 */
import simpleGit from 'simple-git';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';
import { checkoutRemoteBranch } from '../../../../core/git';
import { createPR } from '../../../../core/github/repo';

export const updateReleaseBranchChangelogs = async (
	tmpRepoPath,
	version,
	releaseBranch
) => {
	await checkoutRemoteBranch( tmpRepoPath, releaseBranch );

	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

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

	// Can use git.revparse() here I think.
	const deletionCommitHash = await git.raw( [ 'rev-parse', 'HEAD' ] );
	Logger.notice( `git deletion hash: ${ deletionCommitHash }` );

	// Do readme.txt changes
	Logger.notice( `Updating readme.txt in ${ tmpRepoPath }` );
	execSync( 'php .github/workflows/scripts/release-changelog.php', {
		cwd: tmpRepoPath,
		stdio: 'inherit',
	} );

	Logger.notice(
		`Committing readme.txt changes in ${ branch } on ${ tmpRepoPath }`
	);
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

export const updateTrunkChangelog = async (
	tmpRepoPath,
	version,
	deletionCommitHash
) => {
	Logger.notice( `Deleting changelogs from trunk ${ tmpRepoPath }` );
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );
	await git.checkout( 'trunk' );
	const branch = `delete/${ version }-changelog`;
	Logger.notice( `Committing deletions in ${ branch } on ${ tmpRepoPath }` );
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
