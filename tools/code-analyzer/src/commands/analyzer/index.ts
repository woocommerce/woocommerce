/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { join } from 'path';
import { readFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';
import {
	printTemplateResults,
	printHookResults,
	printSchemaChange,
	printDatabaseUpdates,
} from '../../print';
import {
	getVersionRegex,
	getFilename,
	getPatches,
	getHookName,
	areSchemasEqual,
	getHookDescription,
	getHookChangeType,
} from '../../utils';
import { generatePatch, generateSchemaDiff } from '../../git';

/**
 * Analyzer class
 */
export default class Analyzer extends Command {
	/**
	 * CLI description
	 */
	static description = 'Analyze code changes in WooCommerce Monorepo.';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'compare',
			description:
				'GitHub branch or commit hash to compare against the base branch/commit.',
			required: true,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		base: Flags.string( {
			char: 'b',
			description: 'GitHub base branch or commit hash.',
			default: 'trunk',
		} ),
		output: Flags.string( {
			char: 'o',
			description: 'Output styling.',
			options: [ 'console', 'github' ],
			default: 'console',
		} ),
		source: Flags.string( {
			char: 's',
			description: 'GitHub organization/repository.',
			default: 'woocommerce/woocommerce',
		} ),
		plugin: Flags.string( {
			char: 'p',
			description: 'Plugin to check for',
			options: [ 'core', 'admin', 'beta' ],
			default: 'core',
		} ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( Analyzer );

		this.validateArgs( flags.source );

		const patchContent = generatePatch(
			flags.source,
			args.compare,
			flags.base,
			( e: string ): void => this.error( e )
		);

		const pluginData = this.getPluginData( flags.plugin );
		this.log( `${ pluginData[ 1 ] } Version: ${ pluginData[ 0 ] }` );

		// Run schema diffs only in the monorepo.
		if ( flags.source === 'woocommerce/woocommerce' ) {
			const schemaDiff = await generateSchemaDiff(
				flags.source,
				args.compare,
				flags.base,
				( e: string ): void => this.error( e )
			);

			this.scanChanges(
				patchContent,
				pluginData[ 0 ],
				flags.output,
				schemaDiff
			);
		} else {
			this.scanChanges( patchContent, pluginData[ 0 ], flags.output );
		}
	}

	/**
	 * Validates all of the arguments to make sure
	 *
	 * @param {string} source The GitHub repository we are merging.
	 */
	private validateArgs( source: string ): void {
		// We only support pulling from GitHub so the format needs to match that.
		if ( ! source.match( /^[a-z0-9\-]+\/[a-z0-9\-]+$/ ) ) {
			this.error(
				'The "source" argument must be in "organization/repository" format'
			);
		}
	}

	/**
	 * Get plugin data
	 *
	 * @param {string} plugin Plugin slug.
	 * @return {string[]} Promise.
	 */
	private getPluginData( plugin: string ): string[] {
		/**
		 * List of plugins from our monorepo.
		 */
		const plugins = <any>{
			core: {
				name: 'WooCommerce',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce',
					'woocommerce.php'
				),
			},
			admin: {
				name: 'WooCommerce Admin',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce-admin',
					'woocommerce-admin.php'
				),
			},
			beta: {
				name: 'WooCommerce Beta Tester',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce-beta-tester',
					'woocommerce-beta-tester.php'
				),
			},
		};

		const pluginData = plugins[ plugin ];

		CliUx.ux.action.start( `Getting ${ pluginData.name } version` );

		const content = readFileSync( pluginData.mainFile ).toString();
		const rawVer = content.match( /^\s+\*\s+Version:\s+(.*)/m );

		if ( ! rawVer ) {
			this.error( 'Failed to find plugin version!' );
		}
		const version = rawVer[ 1 ].replace( /\-.*/, '' );

		CliUx.ux.action.stop();

		return [ version, pluginData.name, pluginData.mainFile ];
	}

	/**
	 * Scan patches for changes in templates, hooks and database schema
	 *
	 * @param {string}  content        Patch content.
	 * @param {string}  version        Current product version.
	 * @param {string}  output         Output style.
	 * @param {boolean} schemaEquality if schemas are equal between branches.
	 */
	private scanChanges(
		content: string,
		version: string,
		output: string,
		schemaDiff: {
			[ key: string ]: {
				description: string;
				base: string;
				compare: string;
				method: string;
				areEqual: boolean;
			};
		} | void
	): void {
		const templates = this.scanTemplates( content, version );
		const hooks = this.scanHooks( content, version, output );
		const databaseUpdates = this.scanDatabases( content );

		if ( templates.size ) {
			printTemplateResults(
				templates,
				output,
				'TEMPLATE CHANGES',
				( s: string ): void => this.log( s )
			);
		} else {
			this.log( 'No template changes found' );
		}

		if ( hooks.size ) {
			printHookResults( hooks, output, 'HOOKS', ( s: string ): void =>
				this.log( s )
			);
		} else {
			this.log( 'No new hooks found' );
		}

		if ( ! areSchemasEqual( schemaDiff ) ) {
			printSchemaChange(
				schemaDiff,
				version,
				output,
				( s: string ): void => this.log( s )
			);
		} else {
			this.log( 'No new schema changes found' );
		}

		if ( databaseUpdates ) {
			printDatabaseUpdates(
				databaseUpdates,
				output,
				( s: string ): void => this.log( s )
			);
		} else {
			this.log( 'No database updates found' );
		}
	}
	/**
	 * Scan patches for changes in the database
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @param {string} output  Output style.
	 * @return {object|null}
	 */
	private scanDatabases(
		content: string
	): { updateFunctionName: string; updateFunctionVersion: string } | null {
		CliUx.ux.action.start( 'Scanning database changes' );
		const matchPatches = /^a\/(.+).php/g;
		const patches = getPatches( content, matchPatches );
		const databaseUpdatePatch = patches.find( ( patch ) => {
			const lines = patch.split( '\n' );
			const filepath = getFilename( lines[ 0 ] );
			return filepath.includes( 'class-wc-install.php' );
		} );

		if ( ! databaseUpdatePatch ) {
			return null;
		}

		const updateFunctionRegex = /\+{1,2}\s*'(\d.\d.\d)' => array\(\n\+{1,2}\s*'(.*)',\n\+{1,2}\s*\),/m;
		const match = databaseUpdatePatch.match( updateFunctionRegex );

		if ( ! match ) {
			return null;
		}
		const updateFunctionVersion = match[ 1 ];
		const updateFunctionName = match[ 2 ];
		CliUx.ux.action.stop();
		return { updateFunctionName, updateFunctionVersion };
	}

	/**
	 * Scan patches for changes in templates
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @return {Promise<Map<string, string[]>>} Promise.
	 */
	private scanTemplates(
		content: string,
		version: string
	): Map< string, string[] > {
		CliUx.ux.action.start( 'Scanning template changes' );

		const report: Map< string, string[] > = new Map< string, string[] >();

		if ( ! content.match( /diff --git a\/(.+)\/templates\/(.+)/g ) ) {
			CliUx.ux.action.stop();
			return report;
		}

		const matchPatches = /^a\/(.+)\/templates\/(.+)/g;
		const title = 'Template change detected';
		const patches = getPatches( content, matchPatches );
		const matchVersion = `^(\\+.+\\*.+)(@version)\\s+(${ version.replace(
			/\./g,
			'\\.'
		) }).*`;
		const versionRegex = new RegExp( matchVersion, 'g' );

		for ( const p in patches ) {
			const patch = patches[ p ];
			const lines = patch.split( '\n' );
			const filepath = getFilename( lines[ 0 ] );
			let code = 'warning';
			let message = 'This template may require a version bump!';

			for ( const l in lines ) {
				const line = lines[ l ];

				if ( line.match( versionRegex ) ) {
					code = 'notice';
					message = 'Version bump found';
				}
			}

			if ( code === 'notice' && report.get( filepath ) ) {
				report.set( filepath, [ code, title, message ] );
			} else if ( ! report.get( filepath ) ) {
				report.set( filepath, [ code, title, message ] );
			}
		}

		CliUx.ux.action.stop();
		return report;
	}

	/**
	 * Scan patches for hooks
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @param {string} output  Output style.
	 * @return {Promise<Map<string, Map<string, string[]>>>} Promise.
	 */
	private scanHooks(
		content: string,
		version: string,
		output: string
	): Map< string, Map< string, string[] > > {
		CliUx.ux.action.start( 'Scanning for new hooks' );

		const report: Map< string, Map< string, string[] > > = new Map<
			string,
			Map< string, string[] >
		>();

		if ( ! content.match( /diff --git a\/(.+).php/g ) ) {
			CliUx.ux.action.stop();
			return report;
		}

		const matchPatches = /^a\/(.+).php/g;
		const patches = getPatches( content, matchPatches );
		const verRegEx = getVersionRegex( version );
		const matchHooks = `\/\\*\\*(.*?)@since\\s+(${ verRegEx })(.*?)(apply_filters|do_action)\\((\\s+)?(\\'|\\")(.*?)(\\'|\\")`;
		const newRegEx = new RegExp( matchHooks, 'gs' );

		for ( const p in patches ) {
			const patch = patches[ p ];
			const results = patch.match( newRegEx );
			const hasHookRegex = /apply_filters|do_action/g;
			const hasHook = patch.match( hasHookRegex );
			const hooksList: Map< string, string[] > = new Map<
				string,
				string[]
			>();

			if ( ! results ) {
				if ( hasHook ) {
					this.error(
						'A hook has been introduced or updated without a docBlock. Please add a docBlock.'
					);
				}
				continue;
			}

			const lines = patch.split( '\n' );
			const filepath = getFilename( lines[ 0 ] );

			for ( const raw of results ) {
				// Extract hook name and type.
				const hookName = raw.match(
					/(.*)(do_action|apply_filters)\(\s+'(.*)'/
				);

				if ( ! hookName ) {
					continue;
				}

				const description = getHookDescription( raw );

				const name = getHookName( hookName[ 3 ] );

				if ( ! description ) {
					this.error(
						`Hook ${ name } has no description. Please add a description.`
					);
				}

				const kind =
					hookName[ 2 ] === 'do_action' ? 'action' : 'filter';
				const CLIMessage = `**${ name }** introduced in ${ version }`;
				const GithubMessage = `\\'${ name }\\' introduced in ${ version }`;
				const message =
					output === 'github' ? GithubMessage : CLIMessage;
				const hookChangeType = getHookChangeType( raw );
				const title = `${ hookChangeType } ${ kind } found`;

				if ( ! hookName[ 2 ].startsWith( '-' ) ) {
					hooksList.set( name, [
						'NOTICE',
						title,
						message,
						description,
					] );
				}
			}

			report.set( filepath, hooksList );
		}

		CliUx.ux.action.stop();
		return report;
	}
}
