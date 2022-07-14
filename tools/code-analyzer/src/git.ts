/**
 * External dependencies
 */
import { CliUx } from '@oclif/core';
import { execSync } from 'child_process';
import { join } from 'path';
import { tmpdir } from 'os';
import { mkdirSync, readFileSync, rmSync } from 'fs';
import { simpleGit } from 'simple-git';
import { v4 } from 'uuid';

/**
 * Internal dependencies
 */
import { startWPEnv, stopWPEnv, isValidCommitHash } from './utils';

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
 * @return {string} the path to the cloned repo.
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

	return folderPath;
};

/**
 * Do a git diff of 2 commit hashes (or branches)
 *
 * @param {string} baseDir - baseDir that the repo is in
 * @param {string} hashA   - either a git commit hash or a git branch
 * @param {string} hashB   - either a git commit hash or a git branch
 * @return {string} - diff of the changfiles between the 2 hashes
 */
export const diffHashes = ( baseDir: string, hashA: string, hashB: string ) => {
	const git = simpleGit( { baseDir } );
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
 * @param {string}   repoPath - the url or filepath of the repo to clone.
 * @param {string}   hashA    - commit hash or branch name.
 * @param {string}   hashB    - commit hash or branch name.
 * @param {Function} onError  - the handler to call when an error occurs.
 */
export const generateDiff = async (
	repoPath: string,
	hashA: string,
	hashB: string,
	onError: ( error: string ) => void
) => {
	try {
		const tmpRepoPath = await cloneRepo( repoPath );
		const git = simpleGit( { baseDir: tmpRepoPath } );
		await git.fetch();

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

		const diff = await diffHashes( tmpRepoPath, commitHashA, commitHashB );

		// time to clean up
		rmSync( tmpRepoPath, { force: true, recursive: true } );

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

/**
 * Fetch branches from origin.
 *
 * @param {string}   branch branch/commit hash.
 * @param {Function} error  error print method.
 * @return {boolean} Promise.
 */
export const fetchBranch = (
	branch: string,
	error: ( s: string ) => void
): boolean => {
	CliUx.ux.action.start( `Fetching ${ branch }` );
	const branches = execSync( 'git branch', {
		encoding: 'utf-8',
	} );

	const branchExistsLocally = branches.includes( branch );

	if ( branchExistsLocally ) {
		CliUx.ux.action.stop();
		return true;
	}

	if ( isValidCommitHash( branch ) ) {
		// The hash is valid and available in history. No need to fetch anything.
		return true;
	}

	try {
		// Fetch branch.
		execSync( `git fetch origin ${ branch }` );
		// Create branch.
		execSync( `git branch ${ branch } origin/${ branch }` );
	} catch ( e ) {
		error(
			`Unable to fetch ${ branch }. Supply a valid branch name or commit hash.`
		);
		return false;
	}

	CliUx.ux.action.stop();
	return true;
};

/**
 * Generate a patch file into the temp directory and return its contents
 *
 * @param {string}   source  The GitHub repository.
 * @param {string}   compare Branch/commit hash to compare against the base.
 * @param {string}   base    Base branch/commit hash.
 * @param {Function} error   error print method.
 * @return {string} patch string.
 */
export const generatePatch = (
	source: string,
	compare: string,
	base: string,
	error: ( s: string ) => void
): string => {
	const filename = `${ source }-${ base }-${ compare }.patch`.replace(
		/\//g,
		'-'
	);
	const filepath = join( tmpdir(), filename );

	fetchBranch( base, error );
	fetchBranch( compare, error );

	CliUx.ux.action.start( 'Generating patch for ' + compare );

	try {
		const diffCommand = `git diff ${ base }...${ compare } > ${ filepath }`;
		execSync( diffCommand );
	} catch ( e ) {
		error(
			'Unable to create diff. Check that git origin, base branch, and compare branch all exist.'
		);
	}

	const content = readFileSync( filepath ).toString();

	CliUx.ux.action.stop();
	return content;
};

/**
 * Get all schema strings found in WooCommerce.
 *
 * @param {string}   branch branch being compared.
 * @param {Function} error  Error logging function.
 * @return {Object}	Object of schema strings.
 */
export const getSchema = (
	branch: string,
	error: ( s: string ) => void
): {
	schema: string;
	OrdersTableDataStore: string;
} | void => {
	// Save the current branch for later.
	const currentBranch = execSync( 'git rev-parse --abbrev-ref HEAD' );

	try {
		// Make sure the branch is available.
		fetchBranch( branch, error );
		// Start spinner.
		CliUx.ux.action.start( `Gathering schema from ${ branch }` );

		// Checkout branch to compare
		execSync( `git checkout ${ branch }` );

		const getSchemaPath =
			'wp-content/plugins/woocommerce/bin/wc-get-schema.php';
		// Get the WooCommerce schema from wp cli
		const schema = execSync(
			`wp-env run cli "wp eval-file '${ getSchemaPath }'"`,
			{
				cwd: 'plugins/woocommerce',
				encoding: 'utf-8',
			}
		);
		// Get the OrdersTableDataStore schema.
		const OrdersTableDataStore = execSync(
			'wp-env run cli "wp eval \'echo (new Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore)->get_database_schema();\'"',
			{
				cwd: 'plugins/woocommerce',
				encoding: 'utf-8',
			}
		);
		// Return to the current branch.
		execSync( `git checkout ${ currentBranch }` );

		CliUx.ux.action.stop();
		return {
			schema,
			OrdersTableDataStore,
		};
	} catch ( e ) {
		// Return to the current branch.
		execSync( `git checkout ${ currentBranch }` );

		error( `Unable to get schema for branch ${ branch }. \n${ e }` );
	}
};

/**
 * Generate a schema for each branch being compared.
 *
 * @param {string}   source  The GitHub repository.
 * @param {string}   compare Branch/commit hash to compare against the base.
 * @param {string}   base    Base branch/commit hash.
 * @param {Function} error   error print method.
 * @return {Object|void}     diff object.
 */
export const generateSchemaDiff = async (
	source: string,
	compare: string,
	base: string,
	error: ( s: string ) => void
): Promise< {
	[ key: string ]: {
		description: string;
		base: string;
		compare: string;
		method: string;
		areEqual: boolean;
	};
} | void > => {
	// Be sure the wp-env engine is started.
	await startWPEnv( error );

	const baseSchema = getSchema( base, error );
	const compareSchema = getSchema( compare, error );

	stopWPEnv( error );

	if ( ! baseSchema || ! compareSchema ) {
		return;
	}
	return {
		schema: {
			description: 'WooCommerce Base Schema',
			base: baseSchema.schema,
			compare: compareSchema.schema,
			method: 'WC_Install->get_schema',
			areEqual: baseSchema.schema === compareSchema.schema,
		},
		OrdersTableDataStore: {
			description: 'OrdersTableDataStore Schema',
			base: baseSchema.OrdersTableDataStore,
			compare: compareSchema.OrdersTableDataStore,
			method: 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore->get_database_schema',
			areEqual:
				baseSchema.OrdersTableDataStore ===
				compareSchema.OrdersTableDataStore,
		},
	};
};
