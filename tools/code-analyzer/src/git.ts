/**
 * External dependencies
 */
import { CliUx } from '@oclif/core';
import { execSync } from 'child_process';
import { join } from 'path';
import { tmpdir } from 'os';
import { readFileSync, rmSync } from 'fs';
import { simpleGit } from 'simple-git';
import { v4 } from 'uuid';

/**
 * Internal dependencies
 */
import { startWPEnv, stopWPEnv, isValidCommitHash } from './utils';
export const cloneRepo = async ( repoUrl: string ) => {
	const tempFolderName = v4();
	const git = simpleGit( { baseDir: tmpdir() } );
	await git.clone( repoUrl, tempFolderName );

	return tempFolderName;
};

const isURL = ( maybeUrl: string ) => {
	try {
		new URL( maybeUrl );
	} catch ( e ) {
		return false;
	}
	return true;
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
 * generateDiff generates a diff for a given repo and 2 hashes or branch names.
 *
 * @param {string} repoPath - the url or filepath of the repo to clone.
 * @param {string} hashA    - commit hash or branch name.
 * @param {string} hashB    - commit hash or branch name.
 * @param          onError
 */
export const generateDiff = async (
	repoPath: string,
	hashA: string,
	hashB: string,
	onError: ( error: string ) => void
) => {
	// If a local path is provided first lets check if what we're diffing is a repo.
	if ( ! isURL( repoPath ) ) {
	}

	try {
		const context = await cloneRepo( repoPath );
		const tmpRepoPath = join( tmpdir(), context );

		if ( ! isURL( repoPath ) ) {
			// If its local then we should fetch latest on the clone, in case we are missing a hash or branch being compared.
			await simpleGit( { baseDir: tmpRepoPath } ).fetch();
		}

		const patch = await diffHashes( tmpRepoPath, hashA, hashB );

		console.log( patch );
		console.log( 'cleaning up' );

		// time to clean up
		rmSync( tmpRepoPath, { force: true, recursive: true } );
	} catch ( e ) {
		onError(
			'Unable to create diff. Check that git repo, base hash, and compare hash all exist.'
		);
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
