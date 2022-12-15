/**
 * External dependencies
 */
import { execSync } from 'child_process';
import { join } from 'path';
import { tmpdir } from 'os';
import { mkdirSync } from 'fs';
import { simpleGit } from 'simple-git';
import { v4 } from 'uuid';
import { mkdir, rm } from 'fs/promises';
import { URL } from 'node:url';

/**
 * Get filename from patch
 *
 * @param {string} str String to extract filename from.
 * @return {string} formatted filename.
 */
export const getFilename = ( str: string ): string => {
	return str.replace( /^a(.*)\s.*/, '$1' );
};

/**
 * Get patches
 *
 * @param {string} content Patch content.
 * @param {RegExp} regex   Regex to find specific patches.
 * @return {string[]} Array of patches.
 */
export const getPatches = ( content: string, regex: RegExp ): string[] => {
	const patches = content.split( 'diff --git ' );
	const changes: string[] = [];

	for ( const p in patches ) {
		const patch = patches[ p ];
		const id = patch.match( regex );

		if ( id ) {
			changes.push( patch );
		}
	}

	return changes;
};

/**
 * Check if a string is a valid url.
 *
 * @param {string} maybeURL - the URL string to check
 * @return {boolean} whether the string is a valid URL or not.
 */
const isUrl = ( maybeURL: string ) => {
	try {
		new URL( maybeURL );
		return true;
	} catch ( e ) {
		return false;
	}
};

/**
 * Clone a git repository.
 *
 * @param {string} repoPath - the path (either URL or file path) to the repo to clone.
 * @return {Promise<string>} the path to the cloned repo.
 */
export const cloneRepo = async ( repoPath: string ) => {
	const folderPath = join( tmpdir(), 'code-analyzer-tmp', v4() );
	mkdirSync( folderPath, { recursive: true } );

	const git = simpleGit( { baseDir: folderPath } );
	await git.clone( repoPath, folderPath );

	// If this is a local clone then the simplest way to maintain remote settings is to copy git config across
	if ( ! isUrl( repoPath ) ) {
		execSync( `cp ${ repoPath }/.git/config ${ folderPath }/.git/config` );
	}

	// Update the repo.
	await git.fetch();

	return folderPath;
};

/**
 * Do a minimal sparse checkout of a github repo.
 *
 * @param {string}        githubRepoUrl -     the URL to the repo to checkout.
 * @param {string}        path          - the path to checkout to.
 * @param {Array<string>} directories   - the files or directories to checkout.
 * @return {Promise<string>}  the path to the cloned repo.
 */
export const sparseCheckoutRepo = async (
	githubRepoUrl: string,
	path: string,
	directories: string[]
) => {
	const folderPath = join( tmpdir(), path );

	// clean up if it already exists.
	await rm( folderPath, { recursive: true, force: true } );
	await mkdir( folderPath, { recursive: true } );

	const git = simpleGit( { baseDir: folderPath } );

	await git.clone( githubRepoUrl, folderPath );
	await git.raw( 'sparse-checkout', 'init', { '--cone': null } );
	await git.raw( 'sparse-checkout', 'set', directories.join( ' ' ) );

	return folderPath;
};

/**
 * checkoutRef - checkout a ref in a git repo.
 *
 * @param {string} pathToRepo - the path to the repo to checkout a ref from.
 * @param {string} ref        - the ref to checkout.
 * @return {Response<string>} - the simple-git response.
 */
export const checkoutRef = ( pathToRepo: string, ref: string ) => {
	const git = simpleGit( { baseDir: pathToRepo } );
	return git.checkout( ref );
};

/**
 * Do a git diff of 2 commit hashes (or branches)
 *
 * @param {string}        baseDir      - baseDir that the repo is in
 * @param {string}        hashA        - either a git commit hash or a git branch
 * @param {string}        hashB        - either a git commit hash or a git branch
 * @param {Array<string>} excludePaths - A list of paths to exclude from the diff
 * @return {Promise<string>} - diff of the changes between the 2 hashes
 */
export const diffHashes = (
	baseDir: string,
	hashA: string,
	hashB: string,
	excludePaths: string[] = []
) => {
	const git = simpleGit( { baseDir } );

	if ( excludePaths.length ) {
		return git.diff( [
			`${ hashA }..${ hashB }`,
			'--',
			'.',
			...excludePaths.map( ( ps ) => `:^${ ps }` ),
		] );
	}

	return git.diff( [ `${ hashA }..${ hashB }` ] );
};

/**
 * Determines if a string is a commit hash or not.
 *
 * @param {string} ref - the ref to check
 * @return {boolean} whether the ref is a commit hash or not.
 */
const refIsHash = ( ref: string ) => {
	return /^[0-9a-f]{7,40}$/i.test( ref );
};

/**
 * Get the commit hash for a ref (either branch or commit hash). If a validly
 * formed hash is provided it is returned unmodified.
 *
 * @param {string} baseDir - the dir of the git repo to get the hash from.
 * @param {string} ref     -    Either a commit hash or a branch name.
 * @return {string} - the commit hash of the ref.
 */
export const getCommitHash = async ( baseDir: string, ref: string ) => {
	const isHash = refIsHash( ref );

	// check if its in history, if its not an error will be thrown
	try {
		await simpleGit( { baseDir } ).show( ref );
	} catch ( e ) {
		throw new Error(
			`${ ref } is not a valid commit hash or branch name that exists in git history`
		);
	}

	// If its not a hash we assume its a branch
	if ( ! isHash ) {
		return simpleGit( { baseDir } ).revparse( [ ref ] );
	}

	// Its a hash already
	return ref;
};

/**
 * generateDiff generates a diff for a given repo and 2 hashes or branch names.
 *
 * @param {string}        tmpRepoPath  - filepath to the repo to generate a diff from.
 * @param {string}        hashA        - commit hash or branch name.
 * @param {string}        hashB        - commit hash or branch name.
 * @param {Function}      onError      - the handler to call when an error occurs.
 * @param {Array<string>} excludePaths - A list of directories to exclude from the diff.
 */
export const generateDiff = async (
	tmpRepoPath: string,
	hashA: string,
	hashB: string,
	onError: ( error: string ) => void,
	excludePaths: string[] = []
) => {
	try {
		const git = simpleGit( { baseDir: tmpRepoPath } );

		const validBranches = [ hashA, hashB ].filter(
			( hash ) => ! refIsHash( hash )
		);

		// checking out any branches will automatically track remote branches.
		for ( const validBranch of validBranches ) {
			// Note you can't do checkouts in parallel otherwise the git binary will crash
			await git.checkout( [ validBranch ] );
		}

		// turn both hashes into commit hashes if they are not already.
		const commitHashA = await getCommitHash( tmpRepoPath, hashA );
		const commitHashB = await getCommitHash( tmpRepoPath, hashB );

		const isRepo = await simpleGit( {
			baseDir: tmpRepoPath,
		} ).checkIsRepo();

		if ( ! isRepo ) {
			throw new Error( 'Not a git repository' );
		}

		const diff = await diffHashes(
			tmpRepoPath,
			commitHashA,
			commitHashB,
			excludePaths
		);

		return diff;
	} catch ( e ) {
		if ( e instanceof Error ) {
			onError(
				`Unable to create diff. Check that git repo, base hash, and compare hash all exist.\n Error: ${ e.message }`
			);
		} else {
			onError(
				'Unable to create diff. Check that git repo, base hash, and compare hash all exist.'
			);
		}

		return '';
	}
};
