/**
 * External dependencies
 */
import { join } from 'path';
import { simpleGit } from 'simple-git';
import { execAsync, startWPEnv, stopWPEnv } from 'cli-core/src/util';

export type SchemaDump = {
	schema: string;
	OrdersTableDataStore: string;
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
): Promise< SchemaDump | void > => {
	try {
		const pluginPath = join( tmpRepoPath, 'plugins/woocommerce' );
		const getSchemaPath =
			'wp-content/plugins/woocommerce/bin/wc-get-schema.php';

		// Get the WooCommerce schema from wp cli
		const schemaOutput = await execAsync(
			`wp-env run cli "wp eval-file '${ getSchemaPath }'"`,
			{
				cwd: pluginPath,
				encoding: 'utf-8',
			}
		);

		// Get the OrdersTableDataStore schema.
		const ordersTableOutput = await execAsync(
			'wp-env run cli "wp eval \'echo (new Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore)->get_database_schema();\'"',
			{
				cwd: pluginPath,
				encoding: 'utf-8',
			}
		);

		return {
			schema: schemaOutput.stdout,
			OrdersTableDataStore: ordersTableOutput.stdout,
		};
	} catch ( e ) {
		if ( e instanceof Error ) {
			error( e.message );
		}
	}
};

export type SchemaDiff = {
	name: string;
	description: string;
	base: string;
	compare: string;
	method: string;
	areEqual: boolean;
};

/**
 * Generate a schema for each branch being compared.
 *
 * @param {string}   tmpRepoPath Path to repository used to generate schema diff.
 * @param {string}   compare     Branch/commit hash to compare against the base.
 * @param {string}   base        Base branch/commit hash.
 * @param {Function} build       Build to perform between checkouts.
 * @param {Function} error       error print method.
 * @return {Promise<SchemaDiff[]|null>}     diff object.
 */
export const generateSchemaDiff = async (
	tmpRepoPath: string,
	compare: string,
	base: string,
	build: () => Promise< void > | void,
	error: ( s: string ) => void
): Promise< SchemaDiff[] | null > => {
	const git = simpleGit( {
		baseDir: tmpRepoPath,
		config: [ 'core.hooksPath=/dev/null' ],
	} );

	// Be sure the wp-env engine is started.
	await startWPEnv( tmpRepoPath, error );

	// Force checkout because sometimes a build will generate a lockfile change.
	await git.checkout( base, [ '--force' ] );
	await build();
	const baseSchema = await getSchema(
		tmpRepoPath,
		( errorMessage: string ) => {
			error(
				`Unable to get schema for branch ${ base }. \n${ errorMessage }`
			);
		}
	);

	// Force checkout because sometimes a build will generate a lockfile change.
	await git.checkout( compare, [ '--force' ] );
	await build();
	const compareSchema = await getSchema(
		tmpRepoPath,
		( errorMessage: string ) => {
			error(
				`Unable to get schema for branch ${ compare }. \n${ errorMessage }`
			);
		}
	);

	await stopWPEnv( tmpRepoPath, error );

	if ( ! baseSchema || ! compareSchema ) {
		return null;
	}

	return [
		{
			name: 'schema',
			description: 'WooCommerce Base Schema',
			base: baseSchema.schema,
			compare: compareSchema.schema,
			method: 'WC_Install->get_schema',
			areEqual: baseSchema.schema === compareSchema.schema,
		},
		{
			name: 'OrdersTableDataStore',
			description: 'OrdersTableDataStore Schema',
			base: baseSchema.OrdersTableDataStore,
			compare: compareSchema.OrdersTableDataStore,
			method: 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\OrdersTableDataStore->get_database_schema',
			areEqual:
				baseSchema.OrdersTableDataStore ===
				compareSchema.OrdersTableDataStore,
		},
	];
};
