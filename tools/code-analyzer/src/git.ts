/**
 * External dependencies
 */
import { CliUx } from '@oclif/core';
import { execSync } from 'child_process';
import { join } from 'path';
import { tmpdir } from 'os';
import { mkdirSync } from 'fs';
import { simpleGit } from 'simple-git';
import { v4 } from 'uuid';

/**
 * Internal dependencies
 */
import { startWPEnv, stopWPEnv } from './utils';

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

	// Update the repo.
	await git.fetch();

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
 * @param {string}   tmpRepoPath - filepath to the repo to generate a diff from.
 * @param {string}   hashA       - commit hash or branch name.
 * @param {string}   hashB       - commit hash or branch name.
 * @param {Function} onError     - the handler to call when an error occurs.
 */
export const generateDiff = async (
	tmpRepoPath: string,
	hashA: string,
	hashB: string,
	onError: ( error: string ) => void
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

		const diff = await diffHashes( tmpRepoPath, commitHashA, commitHashB );

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
 * Get all schema strings found in WooCommerce.
 *
 * @param {string}   tmpRepoPath - filepath to the repo to generate a schema from.
 * @param {Function} error       - Error logging function.
 * @return {Object}	Object of schema strings.
 */
export const getSchema = async (
	tmpRepoPath: string,
	error: ( s: string ) => void
): Promise< {
	schema: string;
	OrdersTableDataStore: string;
} | void > => {
	try {
		const pluginPath = join( tmpRepoPath, 'plugins/woocommerce' );
		const getSchemaPath =
			'wp-content/plugins/woocommerce/bin/wc-get-schema.php';

		// Get the WooCommerce schema from wp cli
		const schema = execSync(
			`wp-env run cli "wp eval-file '${ getSchemaPath }'"`,
			{
				cwd: pluginPath,
				encoding: 'utf-8',
			}
		);

		// Get the OrdersTableDataStore schema.
		const OrdersTableDataStore = execSync(
			'wp-env run cli "wp eval \'echo (new Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore)->get_database_schema();\'"',
			{
				cwd: pluginPath,
				encoding: 'utf-8',
			}
		);

		return {
			schema,
			OrdersTableDataStore,
		};
	} catch ( e ) {
		if ( e instanceof Error ) {
			error( e.message );
		}
	}
};

/**
 * Generate a schema for each branch being compared.
 *
 * @param {string}   tmpRepoPath Path to repository used to generate schema diff.
 * @param {string}   compare     Branch/commit hash to compare against the base.
 * @param {string}   base        Base branch/commit hash.
 * @param            build       Build to perform between checkouts.
 * @param {Function} error       error print method.
 * @return {Object|void}     diff object.
 */
export const generateSchemaDiff = async (
	tmpRepoPath: string,
	compare: string,
	base: string,
	build: () => void,
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
	const git = simpleGit( { baseDir: tmpRepoPath } );

	// Be sure the wp-env engine is started.
	await startWPEnv( tmpRepoPath, error );

	CliUx.ux.action.start( `Gathering schema from ${ base }` );

	// Force checkout because sometimes a build will generate a lockfile change.
	await git.checkout( base, [ '--force' ] );
	build();
	const baseSchema = await getSchema(
		tmpRepoPath,
		( errorMessage: string ) => {
			error(
				`Unable to get schema for branch ${ base }. \n${ errorMessage }`
			);
		}
	);
	CliUx.ux.action.stop();

	CliUx.ux.action.start( `Gathering schema from ${ compare }` );

	// Force checkout because sometimes a build will generate a lockfile change.
	await git.checkout( compare, [ '--force' ] );
	build();
	const compareSchema = await getSchema(
		tmpRepoPath,
		( errorMessage: string ) => {
			error(
				`Unable to get schema for branch ${ compare }. \n${ errorMessage }`
			);
		}
	);
	CliUx.ux.action.stop();

	stopWPEnv( tmpRepoPath, error );

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
