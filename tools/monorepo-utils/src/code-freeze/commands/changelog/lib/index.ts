/**
 * External dependencies
 */
import simpleGit from 'simple-git';
import { execSync } from 'child_process';
import { readFile, writeFile } from 'fs/promises';
import path from 'path';
import { readFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { Logger } from '../../../../core/logger';
import { checkoutRemoteBranch } from '../../../../core/git';
import {
	addLabelsToIssue,
	addMilestoneToIssue,
	createPullRequest,
} from '../../../../core/github/repo';
import { Options } from '../types';
import { getToday } from '../../get-version/lib';

const mergeChangelogEntries = (
	readme: string,
	nextLogTitle: string,
	nextLogEntries: string[]
): string => {
	let updatedReadme = readme
		.replace(
			/^= \d+\.\d+\.\d+.* =\n\n\*\*WooCommerce\*\*\n\n/m,
			nextLogTitle
		)
		.trim();

	nextLogEntries.forEach( ( entry ) => {
		const match = entry.match( /^\* (\w+)/ );
		if ( ! match ) return;

		const entryType = match[ 1 ];

		// Find all existing entries of the same type
		const typeRegex = new RegExp( `\\* ${ entryType }\\b.*`, 'gi' );
		const matches = [ ...updatedReadme.matchAll( typeRegex ) ];

		if ( matches.length > 0 ) {
			// Find the last match and insert after it
			const lastMatch = matches[ matches.length - 1 ];
			const insertIndex = lastMatch.index + lastMatch[ 0 ].length;
			updatedReadme =
				updatedReadme.slice( 0, insertIndex ) +
				'\n' +
				entry +
				updatedReadme.slice( insertIndex );
		} else {
			// No existing entries of this type, insert at the end, before the "See changelog" link
			updatedReadme = updatedReadme.replace(
				/\n+(\[See changelog for all versions\])/,
				`\n${ entry }\n\n\n$1`
			);
		}
	} );

	return updatedReadme;
};

/**
 * Processes the next changelog content and extracts the header and entries.
 *
 * @param {string} nextLog     The raw changelog content from NEXT_CHANGELOG.md
 * @param {string} version     The version number for the changelog
 * @param {string} releaseDate The release date for the changelog
 * @return {Object}            An object containing the next log title and entries
 */
const processNextChangelog = (
	nextLog: string,
	version: string,
	releaseDate: string
) => {
	let changelogEntries = nextLog
		.replace(
			/^= \d+\.\d+\.\d+(-.*?)? YYYY-mm-dd =\n\n\*\*WooCommerce\*\*/,
			''
		)
		.trim();

	// Convert PR number to markdown link.
	changelogEntries = changelogEntries.replace(
		/\[#(\d+)\](?!\()/g,
		'[#$1](https://github.com/woocommerce/woocommerce/pull/$1)'
	);

	const entries = changelogEntries
		.split( /\r?\n(?=\* )/ )
		.filter( ( entry ) => entry.trim() );

	return {
		nextLogTitle: `= ${ version } ${ releaseDate } =\n\n**WooCommerce**\n\n`,
		nextLogEntries: entries,
	};
};

/**
 * Perform changelog adjustments after Jetpack Changelogger has run.
 *
 * @param {string}  version         The original plugin version in the branch.
 * @param {string}  override        Time override.
 * @param {boolean} appendChangelog Whether to append the changelog or replace it.
 * @param {string}  tmpRepoPath     Path where the temporary repo is cloned.
 */
const updateReleaseChangelogs = async (
	version: string,
	override: string,
	appendChangelog: boolean,
	tmpRepoPath: string
) => {
	const today = getToday( override );
	const releaseDate = today.toISODate();

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
	const nextLog = await readFile( nextLogFile, 'utf-8' );

	const { nextLogTitle, nextLogEntries } = processNextChangelog(
		nextLog,
		version,
		releaseDate
	);

	if ( appendChangelog ) {
		readme = mergeChangelogEntries( readme, nextLogTitle, nextLogEntries );
	} else {
		// Replace all existing changelog content with the new changelog
		readme = readme.replace(
			/== Changelog ==\n(.*?)\[See changelog for all versions\]/s,
			`== Changelog ==\n\n${ nextLogTitle }${ nextLogEntries.join(
				'\n'
			) }\n\n[See changelog for all versions]`
		);
	}

	// Ensure there are exactly two empty lines between entries and 'See changelog for all versions'.
	readme = readme
		.trim()
		.replace( /\n+(\[See changelog for all versions\])/, `\n\n\n$1` );

	await writeFile( readmeFile, readme );
};

/**
 * Perform changelog operations on the release branch by submitting a pull request. The release branch is a remote branch.
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
	const { owner, name, version, commitDirectToBase, githubActor } = options;
	const mainVersion = version.replace( /\.\d+(-.*)?$/, '' ); // For compatibility with Jetpack changelogger which expects X.Y as version.

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

		const changelogOutput = execSync(
			`pnpm --filter=@woocommerce/plugin-woocommerce changelog write --add-pr-num -n --yes -vvv --use-version ${ mainVersion }`,
			{
				cwd: tmpRepoPath,
				encoding: 'utf-8',
			}
		);

		const noEntriesWritten =
			changelogOutput.includes( `No changes were found` ) ||
			changelogOutput.includes(
				`no changes with content for this write`
			);

		Logger.notice( `Changelog command output: ${ changelogOutput }` );
		Logger.notice( `Committing deleted files in ${ tmpRepoPath }` );
		//Checkout pnpm-lock.yaml to prevent issues in case of an out of date lockfile.
		await git.checkout( 'pnpm-lock.yaml' );
		await git.add( 'plugins/woocommerce/changelog/' );

		// Check if any files were actually deleted.
		const status = await git.status();
		let deletionCommitHash = '';

		if ( status.staged.length > 0 ) {
			await git.commit(
				`Delete changelog files from ${ version } release`
			);
			deletionCommitHash = (
				await git.raw( [ 'rev-parse', 'HEAD' ] )
			 ).trim();
			Logger.notice( `git deletion hash: ${ deletionCommitHash }` );
		} else {
			Logger.notice(
				'No changelog files to delete, skipping deletion commit'
			);
		}

		Logger.notice( `Updating readme.txt in ${ tmpRepoPath }` );
		await updateReleaseChangelogs(
			version,
			options.override,
			options.appendChangelog,
			tmpRepoPath
		);

		Logger.notice(
			`Committing readme.txt changes in ${ branch } on ${ tmpRepoPath }`
		);
		await git.add( 'plugins/woocommerce/readme.txt' );
		await git.commit(
			`Update the readme files for the ${ version } release`
		);
		await git.push(
			'origin',
			commitDirectToBase ? releaseBranch : branch,
			commitDirectToBase ? [] : [ '--force' ]
		);
		await git.checkout( '.' );

		if ( commitDirectToBase ) {
			Logger.notice(
				`Changelog update was committed directly to ${ releaseBranch }`
			);
			return {
				deletionCommitHash,
				prNumber: -1,
			};
		}
		Logger.notice( `Creating PR for ${ branch }` );
		const warningMessage = noEntriesWritten
			? '> [!CAUTION]\n> No entries were written to the changelog. You will be required to manually add a changelog entry before releasing.\n\n'
			: '';
		const pullRequest = await createPullRequest( {
			owner,
			name,
			title: `Release: Prepare the changelog for ${ version }`,
			body: `${ warningMessage }This pull request was automatically generated to prepare the changelog for ${ version }`,
			head: branch,
			base: releaseBranch,
			reviewers: [ githubActor ],
		} );
		Logger.notice( `Pull request created: ${ pullRequest.html_url }` );

		try {
			await addLabelsToIssue( options, pullRequest.number, [
				'Release',
			] );
		} catch {
			Logger.warn(
				`Could not add label "Release" to PR ${ pullRequest.number }`
			);
		}

		try {
			await addMilestoneToIssue(
				options,
				pullRequest.number,
				`${ mainVersion }.0`
			);
		} catch {
			Logger.warn(
				`Could not add milestone "${ mainVersion }.0" to PR ${ pullRequest.number }`
			);
		}

		return {
			deletionCommitHash,
			prNumber: pullRequest.number,
		};
	} catch ( e ) {
		Logger.error( e );
	}
};

/**
 * Perform changelog operations on a given branch by submitting a pull request.
 *
 * @param {Object} options                                 CLI options
 * @param {string} tmpRepoPath                             temp repo path
 * @param {string} releaseBranch                           release branch name
 * @param {Object} releaseBranchChanges                    update data from updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.deletionCommitHash commit from the changelog deletions in updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.prNumber           pr number created in updateReleaseBranchChangelogs
 * @return {number} Update PR number.
 */
export const updateBranchChangelog = async (
	options: Options,
	tmpRepoPath: string,
	releaseBranch: string,
	releaseBranchChanges: { deletionCommitHash: string; prNumber: number }
): Promise< number > => {
	const { owner, name, version, githubActor } = options;
	const { deletionCommitHash, prNumber } = releaseBranchChanges;

	// Skip if there were no changelog files to delete
	if ( ! deletionCommitHash ) {
		Logger.notice(
			`No deletion commit hash found, skipping changelog deletion from ${ releaseBranch }`
		);
		return -1;
	}

	Logger.notice( `Deleting changelogs from trunk ${ tmpRepoPath }` );
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	try {
		await git.checkout( releaseBranch );
		const branch = `delete/${ releaseBranch }-changelog-from-${ version }`;
		Logger.notice(
			`Committing deletions in ${ branch } on ${ tmpRepoPath }`
		);
		await git.checkout( {
			'-b': null,
			[ branch ]: null,
		} );

		// Read plugin file version in branch to determine milestone.
		let milestone = '';
		const pluginFile = readFileSync(
			path.join( tmpRepoPath, 'plugins/woocommerce/woocommerce.php' ),
			'utf8'
		);
		const m = pluginFile.match( /\*\s+Version:\s+(\d+\.\d+)\.\d+/ );

		if ( m ) {
			milestone = `${ m[ 1 ] }.0`;
		}

		try {
			await git.raw( [ 'cherry-pick', deletionCommitHash ] );
		} catch ( e ) {
			if (
				e.message.includes( 'nothing to commit, working tree clean' )
			) {
				Logger.notice(
					'Cherry-pick resulted in no changes, continuing without error.'
				);
				// No need to skip, just continue
			} else {
				throw e; // Re-throw if it's a different error
			}
		}

		await git.push( 'origin', branch, [ '--force' ] );
		Logger.notice( `Creating PR for ${ branch }` );
		const pullRequest = await createPullRequest( {
			owner,
			name,
			title: `Release: Remove ${ version } change files from ${ releaseBranch }`,
			body: `This pull request was automatically generated to remove the changefiles from ${ version } that are compiled into the \`${ releaseBranch }\` ${
				prNumber > 0 ? `branch via #${ prNumber }` : ''
			}`,
			head: branch,
			base: releaseBranch,
			reviewers: [ githubActor ],
		} );
		Logger.notice( `Pull request created: ${ pullRequest.html_url }` );

		try {
			await addLabelsToIssue( options, pullRequest.number, [
				'Release',
			] );
		} catch {
			Logger.warn(
				`Could not add label "Release" to PR ${ pullRequest.number }`
			);
		}

		try {
			await addMilestoneToIssue( options, pullRequest.number, milestone );
		} catch {
			Logger.warn(
				`Could not add milestone "${ milestone }" to PR ${ pullRequest.number }`
			);
		}

		return pullRequest.number;
	} catch ( e ) {
		if ( e.message.includes( `No commits between ${ releaseBranch }` ) ) {
			Logger.notice(
				`No commits between ${ releaseBranch } and the branch, skipping the PR.`
			);
		} else if (
			e.message.includes( 'did not match any file(s) known to git' )
		) {
			Logger.notice(
				`Branch ${ releaseBranch } does not exist, skipping the PR.`
			);
		} else {
			Logger.error( e );
		}
	}
};

/**
 * Perform changelog operations on trunk by submitting a pull request.
 *
 * @param {Object} options                                 CLI options
 * @param {string} tmpRepoPath                             temp repo path
 * @param {Object} releaseBranchChanges                    update data from updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.deletionCommitHash commit from the changelog deletions in updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.prNumber           pr number created in updateReleaseBranchChangelogs
 */
export const updateTrunkChangelog = async (
	options: Options,
	tmpRepoPath: string,
	releaseBranchChanges: { deletionCommitHash: string; prNumber: number }
): Promise< number > => {
	return await updateBranchChangelog(
		options,
		tmpRepoPath,
		'trunk',
		releaseBranchChanges
	);
};

/**
 * Retrieves the WooCommerce version from the trunk branch
 *
 * @param tmpRepoPath cloned repo path
 * @return the WooCommerce version string if found, or `null` if not found.
 */
async function getTrunkWooCommerceVersion(
	tmpRepoPath: string
): Promise< string | null > {
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	await git.checkout( 'trunk' );

	const wooCommercePhpPath = path.join(
		tmpRepoPath,
		'plugins/woocommerce/woocommerce.php'
	);
	const fileContent = readFileSync( wooCommercePhpPath, 'utf8' );

	const versionMatch = fileContent.match( /\*\s+Version:\s+(\d+\.\d+)/ );
	const version = versionMatch ? versionMatch[ 1 ] : null;

	Logger.notice( `WooCommerce trunk version is ${ version }` );

	return version;
}

function getNextVersion( currentVersion: string ) {
	const parts = currentVersion.split( '.' ).map( Number );
	let major = parts[ 0 ];
	let minor = parts[ 1 ];

	minor++;

	// If minor exceeds 9, reset to 0 and increment major
	if ( minor > 9 ) {
		major++;
		minor = 0;
	}

	return `${ major }.${ minor }`;
}

/**
 * Generates a list of release branch names between the target version and the trunk version.
 * Each branch name follows the format `release/{major}.{minor}`.
 *
 * @param targetVersion the target version in the "major.minor" format (e.g., "9.5").
 * @param trunkVersion  the current trunk version in the "major.minor" format (e.g., "8.7").
 * @return An array of branch names representing all release branches between the target and trunk versions.
 */
function getTargetBranches(
	targetVersion: string,
	trunkVersion: string
): string[] {
	const [ currentMajor, currentMinor ] = trunkVersion
		.split( '.' )
		.map( Number );
	const [ targetMajor, targetMinor ] = targetVersion
		.split( '.' )
		.map( Number );

	if (
		targetMajor > currentMajor ||
		( targetMajor === currentMajor && targetMinor >= currentMinor )
	) {
		Logger.notice(
			`Target version ${ targetVersion } is greater than or equal to trunk version ${ trunkVersion }. Skipping intermediate branches.`
		);
		return [];
	}

	const branches = [];
	let version = getNextVersion( targetVersion );

	while ( version !== trunkVersion ) {
		Logger.notice( `Adding intermediate branch for version ${ version }` );
		branches.push( `release/${ version }` );
		version = getNextVersion( version );
	}

	return branches;
}

/**
 * Updates the changelogs for all intermediate branches between the trunk and the target release version.
 *
 * @param          options                                 a list of options
 * @param          tmpRepoPath                             cloned repo path
 * @param {Object} releaseBranchChanges                    update data from updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.deletionCommitHash commit from the changelog deletions in updateReleaseBranchChangelogs
 * @param {Object} releaseBranchChanges.prNumber           pr number created in updateReleaseBranchChangelogs
 */
export const updateIntermediateBranches = async (
	options: Options,
	tmpRepoPath: string,
	releaseBranchChanges: { deletionCommitHash: string; prNumber: number }
): Promise< void > => {
	Logger.notice(
		`Starting intermediate branches update for version ${ options.version }`
	);

	const trunkVersion = await getTrunkWooCommerceVersion( tmpRepoPath );
	if ( ! trunkVersion ) {
		Logger.error( 'Could not determine WooCommerce trunk version.' );
		return;
	}

	const targetBranches = getTargetBranches( options.version, trunkVersion );
	Logger.notice(
		`Target branches to update: ${ targetBranches.join( ', ' ) }`
	);

	for ( const targetBranch of targetBranches ) {
		try {
			await updateBranchChangelog(
				options,
				tmpRepoPath,
				targetBranch,
				releaseBranchChanges
			);
		} catch ( error ) {
			Logger.error(
				`Failed to update ${ targetBranch }: ${ error.message }`
			);
		}
	}
};
