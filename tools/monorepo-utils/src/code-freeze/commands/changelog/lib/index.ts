/**
 * External dependencies
 */
import simpleGit from 'simple-git';
import { execSync } from 'child_process';
import { readFile, writeFile } from 'fs/promises';
import path from 'path';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';
import { checkoutRemoteBranch } from '../../../../core/git';
import { createPullRequest } from '../../../../core/github/repo';
import { Options } from '../types';
import {
	getToday,
	DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
} from '../../get-version/lib';

/**
 * Perform changelog adjustments after Jetpack Changelogger has run.
 *
 * @param {string} override    Time override.
 * @param {string} tmpRepoPath Path where the temporary repo is cloned.
 */
const updateReleaseChangelogs = async (
	override: string,
	tmpRepoPath: string
) => {
	const today = getToday( override );

	const releaseTime = today.plus( {
		days: DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
	} );
	const releaseDate = releaseTime.toISODate();

	const readmeFile = path.join(
		tmpRepoPath,
		'plugins',
		'woocommerce',
		'readme.txt'
	);
	const nextLogFile = path.join(
		tmpRepoPath,
		'plugins',
		'woocommerce',
		'NEXT_CHANGELOG.md'
	);

	let readme = await readFile( readmeFile, 'utf-8' );
	let nextLog = await readFile( nextLogFile, 'utf-8' );

	nextLog = nextLog.replace(
		/= (\d+\.\d+\.\d+) YYYY-mm-dd =/,
		`= $1 ${ releaseDate } =`
	);

	// Convert PR number to markdown link.
	nextLog = nextLog.replace(
		/\[#(\d+)\](?!\()/g,
		'[#$1](https://github.com/woocommerce/woocommerce/pull/$1)'
	);

	readme = readme.replace(
		/== Changelog ==\n(.*?)\[See changelog for all versions\]/s,
		`== Changelog ==\n\n${ nextLog }\n\n[See changelog for all versions]`
	);

	await writeFile( readmeFile, readme );
};

/**
 * Perform changelog operations on release branch by submitting a pull request. The release branch is a remote branch.
 *
 * @param {Object} options       CLI options
 * @param {string} tmpRepoPath   temp repo path
 * @param {string} releaseBranch release branch name. The release branch is a remote branch on Github.
 * @return {Object} update data
 */
export const updateReleaseBranchChangelogs = async (
	options: Options,
	tmpRepoPath: string,
	releaseBranch: string
): Promise< { deletionCommitHash: string; prNumber: number } > => {
	const { owner, name, version, commitDirectToBase } = options;
	try {
		// Do a full checkout so that we can find the correct PR numbers for changelog entries.
		await checkoutRemoteBranch( tmpRepoPath, releaseBranch, false );
	} catch ( e ) {
		if ( e.message.includes( "couldn't find remote ref" ) ) {
			Logger.error(
				`${ releaseBranch } does not exist on ${ owner }/${ name }.`
			);
		}
		Logger.error( e );
	}

	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	const branch = `update/${ version }-changelog`;

	try {
		if ( ! commitDirectToBase ) {
			await git.checkout( {
				'-b': null,
				[ branch ]: null,
			} );
		}

		Logger.notice( `Running the changelog script in ${ tmpRepoPath }` );
		execSync(
			`pnpm --filter=@woocommerce/plugin-woocommerce changelog write --add-pr-num -n -vvv --use-version ${ version }`,
			{
				cwd: tmpRepoPath,
				stdio: 'inherit',
			}
		);
		Logger.notice( `Committing deleted files in ${ tmpRepoPath }` );
		//Checkout pnpm-lock.yaml to prevent issues in case of an out of date lockfile.
		await git.checkout( 'pnpm-lock.yaml' );
		await git.add( 'plugins/woocommerce/changelog/' );
		await git.commit( `Delete changelog files from ${ version } release` );
		const deletionCommitHash = await git.raw( [ 'rev-parse', 'HEAD' ] );
		Logger.notice( `git deletion hash: ${ deletionCommitHash }` );

		Logger.notice( `Updating readme.txt in ${ tmpRepoPath }` );
		await updateReleaseChangelogs( options.override, tmpRepoPath );

		Logger.notice(
			`Committing readme.txt changes in ${ branch } on ${ tmpRepoPath }`
		);
		await git.add( 'plugins/woocommerce/readme.txt' );
		await git.commit(
			`Update the readme files for the ${ version } release`
		);
		await git.push( 'origin', commitDirectToBase ? releaseBranch : branch );
		await git.checkout( '.' );

		if ( commitDirectToBase ) {
			Logger.notice(
				`Changelog update was committed directly to ${ releaseBranch }`
			);
			return {
				deletionCommitHash: deletionCommitHash.trim(),
				prNumber: -1,
			};
		}
		Logger.notice( `Creating PR for ${ branch }` );
		const pullRequest = await createPullRequest( {
			owner,
			name,
			title: `Release: Prepare the changelog for ${ version }`,
			body: `This pull request was automatically generated to prepare the changelog for ${ version }`,
			head: branch,
			base: releaseBranch,
		} );
		Logger.notice( `Pull request created: ${ pullRequest.html_url }` );
		return {
			deletionCommitHash: deletionCommitHash.trim(),
			prNumber: pullRequest.number,
		};
	} catch ( e ) {
		Logger.error( e );
	}
};

/**
 * Perform changelog operations on trunk by submitting a pull request.
 *
 * @param {Object} options                                 CLI options
 * @param {string} tmpRepoPath                             temp repo path
 * @param {string} releaseBranch                           release branch name
 * @param {Object} releaseBranchChanges                    update data from updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.deletionCommitHash commit from the changelog deletions in updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.prNumber           pr number created in updateReleaseBranchChangelogs
 */
export const updateTrunkChangelog = async (
	options: Options,
	tmpRepoPath: string,
	releaseBranch: string,
	releaseBranchChanges: { deletionCommitHash: string; prNumber: number }
): Promise< void > => {
	const { owner, name, version } = options;
	const { deletionCommitHash, prNumber } = releaseBranchChanges;
	Logger.notice( `Deleting changelogs from trunk ${ tmpRepoPath }` );
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	try {
		await git.checkout( 'trunk' );
		const branch = `delete/${ version }-changelog`;
		Logger.notice(
			`Committing deletions in ${ branch } on ${ tmpRepoPath }`
		);
		await git.checkout( {
			'-b': null,
			[ branch ]: null,
		} );
		await git.raw( [ 'cherry-pick', deletionCommitHash ] );
		await git.push( 'origin', branch );
		Logger.notice( `Creating PR for ${ branch }` );
		const pullRequest = await createPullRequest( {
			owner,
			name,
			title: `Release: Remove ${ version } change files`,
			body: `This pull request was automatically generated to remove the changefiles from ${ version } that are compiled into the \`${ releaseBranch }\` ${
				prNumber > 0 ? `branch via #${ prNumber }` : ''
			}`,
			head: branch,
			base: 'trunk',
		} );
		Logger.notice( `Pull request created: ${ pullRequest.html_url }` );
	} catch ( e ) {
		Logger.error( e );
	}
};
